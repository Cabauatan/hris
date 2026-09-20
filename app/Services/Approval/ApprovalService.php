<?php

namespace App\Services\Approval;

use App\Models\ApprovalAction;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CtoRequest;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\Attendance\AttendanceCorrectionService;
use App\Services\Attendance\OvertimeService;
use App\Services\Cto\CtoService;
use App\Services\Leave\LeaveService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    private const REQUEST_TYPES = [
        'leave_request' => LeaveRequest::class,

        'overtime_request' =>
            OvertimeRequest::class,

        'attendance_correction_request' =>
            AttendanceCorrectionRequest::class,

        'cto_request' => CtoRequest::class,
    ];

    public function __construct(
        private readonly LeaveService $leaveService,
        private readonly OvertimeService $overtimeService,
        private readonly AttendanceCorrectionService $attendanceCorrectionService,
        private readonly CtoService $ctoService,
        private readonly ApprovalDelegationService $delegationService,
    ) {
    }

    public function approve(
        string $requestType,
        int $requestId,
        User $actor,
        array $data = []
    ): Model {
        return DB::transaction(function () use (
            $requestType,
            $requestId,
            $actor,
            $data
        ) {
            $request = $this->lockRequest(
                $requestType,
                $requestId
            );

            $this->ensurePending($request);

            /*
             * Policies will later establish the actor's normal
             * authority. Delegation is an additional route,
             * not a replacement for authorization.
             */
            $this->ensureActiveActor($actor);

            $result = match ($requestType) {
                'leave_request' =>
                    $this->leaveService->approve(
                        $request,
                        $actor,
                        $data['remarks'] ?? null
                    ),

                'overtime_request' =>
                    $this->overtimeService->approve(
                        $request,
                        $this->requiredApprovedMinutes($data),
                        $actor,
                        $data['remarks'] ?? null
                    ),

                'attendance_correction_request' =>
                    $this->attendanceCorrectionService->approve(
                        $request,
                        $actor,
                        $data['remarks'] ?? null
                    ),

                'cto_request' =>
                    $this->ctoService->approve(
                        $request,
                        $this->requiredApprovedMinutes($data),
                        $actor,
                        $data['remarks'] ?? null
                    ),
            };

            $this->recordAction(
                $requestType,
                $requestId,
                $actor,
                'approved',
                $data
            );

            return $result;
        });
    }

    public function reject(
        string $requestType,
        int $requestId,
        User $actor,
        string $remarks
    ): Model {
        return DB::transaction(function () use (
            $requestType,
            $requestId,
            $actor,
            $remarks
        ) {
            $request = $this->lockRequest(
                $requestType,
                $requestId
            );

            $this->ensurePending($request);
            $this->ensureActiveActor($actor);

            $result = match ($requestType) {
                'leave_request' =>
                    $this->leaveService->reject(
                        $request,
                        $actor,
                        $remarks
                    ),

                'overtime_request' =>
                    $this->overtimeService->reject(
                        $request,
                        $actor,
                        $remarks
                    ),

                'attendance_correction_request' =>
                    $this->attendanceCorrectionService->reject(
                        $request,
                        $actor,
                        $remarks
                    ),

                'cto_request' =>
                    $this->ctoService->reject(
                        $request,
                        $actor,
                        $remarks
                    ),
            };

            $this->recordAction(
                $requestType,
                $requestId,
                $actor,
                'rejected',
                [
                    'remarks' => $remarks,
                ]
            );

            return $result;
        });
    }

    private function lockRequest(
        string $requestType,
        int $requestId
    ): Model {
        $class = self::REQUEST_TYPES[$requestType] ?? null;

        if (! $class) {
            throw ValidationException::withMessages([
                'request_type' =>
                    'Unsupported approval request type.',
            ]);
        }

        return $class::query()
            ->lockForUpdate()
            ->findOrFail($requestId);
    }

    private function ensurePending(
        Model $request
    ): void {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending requests may be acted upon.',
            ]);
        }
    }

    private function ensureActiveActor(
        User $actor
    ): void {
        if (! $actor->is_active) {
            throw ValidationException::withMessages([
                'actor' =>
                    'An inactive user cannot perform approval actions.',
            ]);
        }
    }

    private function requiredApprovedMinutes(
        array $data
    ): int {
        if (
            ! array_key_exists('approved_minutes', $data)
            || ! is_numeric($data['approved_minutes'])
        ) {
            throw ValidationException::withMessages([
                'approved_minutes' =>
                    'Approved minutes are required.',
            ]);
        }

        return (int) $data['approved_minutes'];
    }

    private function recordAction(
        string $requestType,
        int $requestId,
        User $actor,
        string $action,
        array $data
    ): ApprovalAction {
        return ApprovalAction::create([
            'request_type' => $requestType,
            'request_id' => $requestId,

            'action' => $action,

            'acted_by_user_id' => $actor->id,

            'remarks' => $data['remarks'] ?? null,
        ]);
    }
}