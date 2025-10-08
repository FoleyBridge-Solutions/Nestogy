<?php

namespace App\Policies;

use App\Policies\Concerns\RecurringAnalyticsPolicyMethods;
use App\Policies\Concerns\RecurringCrudPolicyMethods;
use App\Policies\Concerns\RecurringOperationsPolicyMethods;
use App\Policies\Concerns\RecurringTaxReportPolicyMethods;
use App\Policies\Concerns\RecurringUsagePolicyMethods;

class RecurringPolicy
{
    use RecurringCrudPolicyMethods;
    use RecurringOperationsPolicyMethods;
    use RecurringUsagePolicyMethods;
    use RecurringTaxReportPolicyMethods;
    use RecurringAnalyticsPolicyMethods;
}
