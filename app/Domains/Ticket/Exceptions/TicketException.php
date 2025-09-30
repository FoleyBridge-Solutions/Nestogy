<?php

namespace App\Domains\Ticket\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\PermissionException as BasePermissionException;
use App\Exceptions\ServiceException;
use App\Exceptions\ValidationException as BaseValidationException;

/**
 * Base Ticket Exception
 */
class TicketException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your ticket request.';
    }
}

/**
 * Ticket Validation Exception
 */
class TicketValidationException extends BaseValidationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The ticket data provided is invalid.';
    }
}

/**
 * Ticket Not Found Exception
 */
class TicketNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $ticketId = null, array $context = [])
    {
        parent::__construct('Ticket', $ticketId, $context);
    }
}

/**
 * Ticket Permission Exception
 */
class TicketPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Ticket', $context);
    }
}

/**
 * Ticket Service Exception
 */
class TicketServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A ticket service error occurred. Please try again later.';
    }
}

/**
 * Ticket Business Exception
 */
class TicketBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A ticket business rule was violated.';
    }
}

/**
 * Ticket Status Exception
 */
class TicketStatusException extends TicketBusinessException
{
    public function __construct(string $action, string $currentStatus, array $context = [])
    {
        parent::__construct(
            "Cannot {$action} ticket in {$currentStatus} status",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'current_status' => $currentStatus,
            ]),
            "This action cannot be performed on a ticket with status: {$currentStatus}.",
            400
        );
    }
}

/**
 * Ticket Assignment Exception
 */
class TicketAssignmentException extends TicketBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Ticket assignment failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Unable to assign ticket: {$reason}",
            400
        );
    }
}

/**
 * Ticket SLA Exception
 */
class TicketSlaException extends TicketBusinessException
{
    public function __construct(string $slaViolation, array $context = [])
    {
        parent::__construct(
            "SLA violation: {$slaViolation}",
            400,
            null,
            array_merge($context, ['sla_violation' => $slaViolation]),
            "SLA requirements not met: {$slaViolation}",
            400
        );
    }
}

/**
 * Ticket Escalation Exception
 */
class TicketEscalationException extends TicketBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Ticket escalation failed: {$reason}",
            500,
            null,
            array_merge($context, ['reason' => $reason]),
            "Unable to escalate ticket: {$reason}",
            500
        );
    }
}

/**
 * Ticket Time Tracking Exception
 */
class TicketTimeTrackingException extends TicketException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing time tracking information.';
    }
}

/**
 * Ticket Workflow Exception
 */
class TicketWorkflowException extends TicketBusinessException
{
    public function __construct(string $workflowStep, string $reason, array $context = [])
    {
        parent::__construct(
            "Workflow step '{$workflowStep}' failed: {$reason}",
            400,
            null,
            array_merge($context, [
                'workflow_step' => $workflowStep,
                'reason' => $reason,
            ]),
            "Workflow process failed at step: {$workflowStep}",
            400
        );
    }
}

/**
 * Ticket Email Processing Exception
 */
class TicketEmailException extends TicketException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing email for the ticket.';
    }
}
