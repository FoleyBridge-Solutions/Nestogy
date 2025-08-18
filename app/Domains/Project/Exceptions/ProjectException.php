<?php

namespace App\Domains\Project\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException as BaseValidationException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\PermissionException as BasePermissionException;
use App\Exceptions\ServiceException;

/**
 * Base Project Exception
 */
class ProjectException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your project request.';
    }
}

/**
 * Project Validation Exception
 */
class ProjectValidationException extends BaseValidationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The project data provided is invalid.';
    }
}

/**
 * Project Not Found Exception
 */
class ProjectNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $projectId = null, array $context = [])
    {
        parent::__construct('Project', $projectId, $context);
    }
}

/**
 * Project Permission Exception
 */
class ProjectPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Project', $context);
    }
}

/**
 * Project Service Exception
 */
class ProjectServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A project service error occurred. Please try again later.';
    }
}

/**
 * Project Business Exception
 */
class ProjectBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A project business rule was violated.';
    }
}

/**
 * Project Status Exception
 */
class ProjectStatusException extends ProjectBusinessException
{
    public function __construct(string $action, string $currentStatus, array $context = [])
    {
        parent::__construct(
            "Cannot {$action} project in {$currentStatus} status",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'current_status' => $currentStatus,
            ]),
            "This action cannot be performed on a project with status: {$currentStatus}.",
            400
        );
    }
}

/**
 * Project Task Exception
 */
class ProjectTaskException extends ProjectException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing project task information.';
    }
}

/**
 * Project Member Exception
 */
class ProjectMemberException extends ProjectBusinessException
{
    public function __construct(string $action, string $reason, array $context = [])
    {
        parent::__construct(
            "Project member {$action} failed: {$reason}",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'reason' => $reason,
            ]),
            "Unable to {$action} project member: {$reason}",
            400
        );
    }
}

/**
 * Project Milestone Exception
 */
class ProjectMilestoneException extends ProjectBusinessException
{
    public function __construct(string $action, string $reason, array $context = [])
    {
        parent::__construct(
            "Project milestone {$action} failed: {$reason}",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'reason' => $reason,
            ]),
            "Unable to {$action} project milestone: {$reason}",
            400
        );
    }
}

/**
 * Project Budget Exception
 */
class ProjectBudgetException extends ProjectBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Project budget violation: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Project budget issue: {$reason}",
            400
        );
    }
}

/**
 * Project Timeline Exception
 */
class ProjectTimelineException extends ProjectBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Project timeline issue: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Project timeline conflict: {$reason}",
            400
        );
    }
}