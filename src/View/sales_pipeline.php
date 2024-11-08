<div class="row mb-3">
    <div class="col">
        <div class="card">
            <div class="card-header header-elements">
                <h5 class="card-header-title">Sales Pipeline</h5>
                <div class="card-header-elements ms-auto">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Add Opportunity</a></li>
                        <li><a class="dropdown-item" href="#">Add Lead</a></li>
                        <li><a class="dropdown-item" href="#">Add Qualified Lead</a></li>
                        <li><a class="dropdown-item" href="#">Add Contact</a></li>
                        <li><a class="dropdown-item" href="#">Add Landing</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="nav-align-left">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-opportunities" aria-controls="navs-left-align-opportunities" aria-selected="true">
                                Opportunities
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-leads" aria-controls="navs-left-align-leads" aria-selected="false">
                                Leads
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-qualified_leads" aria-controls="navs-left-align-qualified_leads" aria-selected="false">
                                Qualified Leads
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-contacts" aria-controls="navs-left-align-contacts" aria-selected="false">
                                Contacts
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-landings" aria-controls="navs-left-align-landings" aria-selected="false">
                                Landings
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-left-align-meetings" aria-controls="navs-left-align-meetings" aria-selected="false">
                                Meetings
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="navs-left-align-opportunities">
                            <?php include 'sales_pipeline/opportunities.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-leads">
                            <?php include 'sales_pipeline/leads.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-qualified_leads">
                            <?php include 'sales_pipeline/qualified_leads.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-contacts">
                            <?php include 'sales_pipeline/contacts.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-landings">
                            <?php include 'sales_pipeline/landings.php'; ?>
                        </div>
                        <div class="tab-pane fade" id="navs-left-align-meetings">
                            <?php include 'sales_pipeline/meetings.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>