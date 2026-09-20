<?php

namespace App\Services\Payslip;

use App\Models\Payslip;
use App\Models\PayrollEmployeeResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipService
{
    private string $disk = 'local';

    public function createDraft(
        PayrollEmployeeResult $result
    ): Payslip {
        return DB::transaction(function () use ($result) {
            $result = PayrollEmployeeResult::query()
                ->with([
                    'run.period',
                    'employee',
                ])
                ->lockForUpdate()
                ->findOrFail($result->id);

            /*
             * Payslip must represent a stable payroll result.
             *
             * Adapt allowed result status to the exact finalized
             * lifecycle used by PayrollRunService.
             */
            if ($result->status !== 'finalized') {
                throw ValidationException::withMessages([
                    'payroll_result' =>
                        'A payslip may only be created from a finalized payroll result.',
                ]);
            }

            $existing = Payslip::query()
                ->where(
                    'payroll_employee_result_id',
                    $result->id
                )
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return Payslip::create([
                'payroll_employee_result_id' => $result->id,
                'employee_id' => $result->employee_id,

                'payslip_number' =>
                    $this->generatePayslipNumber($result),

                'status' => 'draft',

                'pdf_path' => null,
            ]);
        });
    }

    public function publish(
        Payslip $payslip,
        User $actor
    ): Payslip {
        return DB::transaction(function () use (
            $payslip,
            $actor
        ) {
            $payslip = Payslip::query()
                ->with([
                    'result.run.period',
                    'result.earnings',
                    'result.deductions',
                    'result.governmentContributions',
                    'employee',
                ])
                ->lockForUpdate()
                ->findOrFail($payslip->id);

            if ($payslip->status === 'published') {
                return $payslip;
            }

            if ($payslip->status === 'revoked') {
                throw ValidationException::withMessages([
                    'payslip' =>
                        'A revoked payslip cannot be published again.',
                ]);
            }

            $this->validateOwnership($payslip);

            if ($payslip->result->status !== 'finalized') {
                throw ValidationException::withMessages([
                    'payroll_result' =>
                        'The payroll result must be finalized before publishing its payslip.',
                ]);
            }

            /*
             * PDF generation is intentionally delegated.
             *
             * This service should receive/generate presentation
             * content from the finalized payroll snapshot only.
             */
            $pdfPath = $this->generatePdf($payslip);

            $payslip->update([
                'status' => 'published',
                'pdf_path' => $pdfPath,
                'published_at' => now(),
                'published_by_user_id' => $actor->id,
            ]);

            return $payslip->refresh();
        });
    }

    public function revoke(
        Payslip $payslip,
        User $actor,
        string $reason
    ): Payslip {
        return DB::transaction(function () use (
            $payslip,
            $actor,
            $reason
        ) {
            $payslip = Payslip::query()
                ->lockForUpdate()
                ->findOrFail($payslip->id);

            if ($payslip->status !== 'published') {
                throw ValidationException::withMessages([
                    'payslip' =>
                        'Only a published payslip may be revoked.',
                ]);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' =>
                        'A reason is required when revoking a payslip.',
                ]);
            }

            $payslip->update([
                'status' => 'revoked',

                'revoked_at' => now(),
                'revoked_by_user_id' => $actor->id,
                'revocation_reason' => $reason,
            ]);

            /*
             * Do NOT modify PayrollEmployeeResult.
             * Revocation affects the payslip publication,
             * not the finalized payroll calculation.
             */

            return $payslip->refresh();
        });
    }

    public function download(
        Payslip $payslip
    ): StreamedResponse {
        if ($payslip->status !== 'published') {
            abort(404);
        }

        if (! $payslip->pdf_path) {
            abort(404, 'Payslip file not found.');
        }

        $disk = Storage::disk($this->disk);

        if (! $disk->exists($payslip->pdf_path)) {
            abort(404, 'Payslip file not found.');
        }

        $stream = $disk->readStream(
            $payslip->pdf_path
        );

        if ($stream === false) {
            abort(404, 'Payslip file not found.');
        }

        $downloadName =
            $payslip->payslip_number . '.pdf';

        return response()->streamDownload(
            static function () use ($stream): void {
                fpassthru($stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            $downloadName,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    private function validateOwnership(
        Payslip $payslip
    ): void {
        if (
            $payslip->employee_id
            !== $payslip->result->employee_id
        ) {
            throw ValidationException::withMessages([
                'employee_id' =>
                    'Payslip employee does not match the payroll result employee.',
            ]);
        }
    }

    private function generatePayslipNumber(
        PayrollEmployeeResult $result
    ): string {
        /*
         * UUID suffix prevents collision.
         *
         * If the company later requires sequential payslip numbers,
         * replace this with a dedicated numbering strategy.
         */
        return sprintf(
            'PS-%s-%s',
            now()->format('Ymd'),
            strtoupper(
                substr(
                    str_replace('-', '', (string) Str::uuid()),
                    0,
                    10
                )
            )
        );
    }

    private function generatePdf(
        Payslip $payslip
    ): string {
        /*
         * PDF renderer will be implemented separately.
         *
         * Important:
         * - private storage only
         * - finalized result snapshots only
         * - never calculate payroll here
         */

        throw ValidationException::withMessages([
            'pdf' =>
                'Payslip PDF renderer has not yet been configured.',
        ]);
    }
}