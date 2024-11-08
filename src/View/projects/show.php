<div class="row">
	<div class="col-md-12 col-lg-4 d-flex flex-column" style="height: calc(100vh - 12rem);">
		<div class="card h-100">
			<div class="card-header d-flex justify-content-between">
				<h5 class="card-title m-0 me-2">Activity Timeline</h5>
				<div class="dropdown">
					<button class="btn text-muted p-0" type="button" id="timelineWapper" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<i class="bx bx-dots-vertical-rounded bx-lg"></i>
					</button>
					<div class="dropdown-menu dropdown-menu-end" aria-labelledby="timelineWapper">
						<a class="dropdown-item" href="javascript:void(0);">Select All</a>
						<a class="dropdown-item" href="javascript:void(0);">Refresh</a>
						<a class="dropdown-item" href="javascript:void(0);">Share</a>
					</div>
				</div>
			</div>
			<div class="card-body pt-2">
				<ul class="timeline mb-0">
					<?php foreach ($project_timeline as $timeline): ?>
						<li class="timeline-item timeline-item-transparent">
							<span class="timeline-point timeline-point-<?= $timeline['project_note_created_by'] != $_SESSION['user_id'] ? 'primary' : 'secondary'; ?>"></span>
							<div class="timeline-event">
								<div class="timeline-header mb-3">
									<h6 class="mb-0"><?= $timeline['project_note_title'] ?? $timeline['ticket_reply_subject']; ?></h6>
									<small class="text-muted"><?= date('M j, Y g:i A', strtotime($timeline['project_note_date'] ?? date('Y-m-d H:i:s', strtotime($timeline['ticket_reply_created_at'])))); ?></small>
								</div>
								<p class="mb-2">
									<?= $timeline['project_note_description'] ?? $timeline['ticket_reply']; ?>
								</p>
								<small class="text-muted">By <?= $timeline['user_name']; ?></small>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
	<div class="col-md-12 col-lg-5 d-flex flex-column" style="height: calc(100vh - 12rem);">
		<div class="card mb-3">
			<div class="card-header">
				<h5 class="card-title"><i class="<?= $project['project_icon'] ?? 'bx bxs-objects-vertical-center'; ?>"></i> <?php echo $project['project_name']; ?></h5>
			</div>
			<div class="card-body">
				<p class="card-text"><?php echo $project['project_description']; ?></p>
			</div>
		</div>
		<div class="card mb-3">
			<div class="card-header header-elements">
				<h5 class="card-header-title">Project Notes</h5>
			</div>
			<div class="card-body">
				<form action="/post.php" method="post">
					<input type="hidden" name="project_note_project_id" value="<?= $project['project_id']; ?>">
					<div class="mb-3">
						<label for="project_note_title" class="form-label">Title</label>
						<input type="text" class="form-control" id="project_note_title" name="project_note_title" required>
					</div>
					<div class="mb-3">
						<label for="project_note_description" class="form-label">Description</label>
						<textarea class="form-control" id="project_note_description" name="project_note_description" rows="3" required></textarea>
					</div>
					<button type="submit" class="btn btn-primary" name="project_note_add">
						<i class="fas fa-plus"></i>
						Add Note
					</button>
				</form>
			</div>
		</div>
		<div class="card flex-grow-1">
			<div class="card-header header-elements">
				<h5 class="card-header-title">Tickets</h5>
				<div class="card-header-elements ms-auto">
					<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
						<i class="fas fa-plus"></i>
					</button>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_add_modal.php?project_id=<?= $project['project_id']; ?>">Create New Ticket</a></li>
						<li><a class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="project_ticket_add_modal.php?project_id=<?= $project['project_id']; ?>">Add Existing Ticket</a></li>
					</ul>
				</div>
			</div>
			<div class="card-body overflow-auto">
				<table class="table">
					<thead>
						<tr>
							<th>Ticket Number</th>
							<th>Title</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tickets as $ticket): ?>
							<tr>
								<td><a href="?page=ticket&ticket_id=<?= $ticket['ticket_id']; ?>"><?= $ticket['ticket_prefix']; ?><?= $ticket['ticket_id']; ?></a></td>
								<td><?= $ticket['ticket_subject']; ?></td>
								<td><?= $ticket['ticket_status_name']; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="col-md-12 col-lg-3 d-flex flex-column" style="height: calc(100vh - 12rem);">
		<div class="card mb-3">
			<div class="card-header header-elements">
				<h5 class="card-header-title">Project <?= $project['project_prefix']; ?><?= $project['project_number']; ?></h5>
				<div class="card-header-elements ms-auto">
					<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
						<i class="fas fa-pencil-alt"></i>
					</button>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="project_edit_modal.php?project_id=<?= $project['project_id']; ?>">Edit Project</a></li>
					</ul>
				</div>
			</div>
			<div class="card-body">
				<table class="table">
					<tr>
						<td>Status</td>
						<td><?= $project['project_status']; ?></td>
					</tr>
					<tr>
						<td>Due Date</td>
						<td><?= $project['project_due']; ?></td>
					</tr>
					<tr>
						<td>Manager</td>
						<td><?= $project['user_name']; ?></td>
					</tr>
					<tr>
						<td>Client</td>
						<td><?= $project['client_name']; ?></td>
					</tr>
				</table>
			</div>
		</div>
		<div class="card flex-grow-1">
			<div class="card-header header-elements">
				<h5 class="card-header-title">Tasks</h5>
			</div>
			<div class="card-body">
				<table class="table">
					<thead>
						<tr>
							<th>Task</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tasks as $task): ?>
							<tr>
								<td><?= $task['task_name']; ?></td>
								<td><?php
									if ($task['task_completed_by'] == null) {
										echo '<span class="badge bg-warning">In Progress</span>';
									} else {
										echo '<span class="badge bg-success">Completed</span>';
									}
									?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>