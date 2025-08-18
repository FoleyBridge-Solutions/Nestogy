<?php

namespace App\Domains\Asset\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException as BaseValidationException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\PermissionException as BasePermissionException;
use App\Exceptions\ServiceException;

/**
 * Base Asset Exception
 */
class AssetException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your asset request.';
    }
}

/**
 * Asset Validation Exception
 */
class AssetValidationException extends BaseValidationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The asset data provided is invalid.';
    }
}

/**
 * Asset Not Found Exception
 */
class AssetNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $assetId = null, array $context = [])
    {
        parent::__construct('Asset', $assetId, $context);
    }
}

/**
 * Asset Permission Exception
 */
class AssetPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Asset', $context);
    }
}

/**
 * Asset Service Exception
 */
class AssetServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An asset service error occurred. Please try again later.';
    }
}

/**
 * Asset Business Exception
 */
class AssetBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An asset business rule was violated.';
    }
}

/**
 * Asset Status Exception
 */
class AssetStatusException extends AssetBusinessException
{
    public function __construct(string $action, string $currentStatus, array $context = [])
    {
        parent::__construct(
            "Cannot {$action} asset in {$currentStatus} status",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'current_status' => $currentStatus,
            ]),
            "This action cannot be performed on an asset with status: {$currentStatus}.",
            400
        );
    }
}

/**
 * Asset Maintenance Exception
 */
class AssetMaintenanceException extends AssetException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing asset maintenance information.';
    }
}

/**
 * Asset Warranty Exception
 */
class AssetWarrantyException extends AssetException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing asset warranty information.';
    }
}

/**
 * Asset Depreciation Exception
 */
class AssetDepreciationException extends AssetException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while calculating asset depreciation.';
    }
}

/**
 * Asset Location Exception
 */
class AssetLocationException extends AssetBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Asset location error: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Asset location issue: {$reason}",
            400
        );
    }
}

/**
 * Asset Assignment Exception
 */
class AssetAssignmentException extends AssetBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Asset assignment failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Unable to assign asset: {$reason}",
            400
        );
    }
}

/**
 * Asset Import Exception
 */
class AssetImportException extends AssetException
{
    protected array $failedRows;

    public function __construct(string $message, array $failedRows = [], array $context = [])
    {
        $this->failedRows = $failedRows;
        
        parent::__construct(
            $message,
            422,
            null,
            array_merge($context, ['failed_rows' => $failedRows]),
            'Some assets could not be imported due to validation errors.',
            422
        );
    }

    public function getFailedRows(): array
    {
        return $this->failedRows;
    }
}