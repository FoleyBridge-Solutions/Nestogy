<?php

namespace App\Domains\PhysicalMail\Exceptions;

use App\Exceptions\NotFoundException;

class TemplateNotFoundException extends NotFoundException
{
    public function __construct(string $templateName, array $context = [])
    {
        parent::__construct(
            'Physical mail template',
            $templateName,
            array_merge($context, ['template_name' => $templateName])
        );
    }
}
