<div class="thumbnail">
	<div class="caption">
		<a href="#" class="btn btn-small tooltip-top pull-right" title="<?php echo lang('action.edit users on this review', 1);?>"><i class="icon-pencil icon-50"></i></a>

		<h3><?php echo lang('[[short.arc.involved|users]]', 1);?></h3>

		<p>
			<?php foreach ($app->reviewers as $rev): ?>
				<span class="label"><?php echo $rev->name;?></span>
			<?php endforeach;?>
		</p>
	</div>
</div><br>

<div class="well well-small">
	<h2><?php echo $app->character->name(false);?> <small><?php echo $app->position->name;?></small></h2>

	<p><strong>Applied on:</strong> <?php echo $app->created_at;?></p>

	<div class="row">
		<div class="span6">
			<div class="control-group">
				<label class="control-label"><?php echo lang('simming experience', 2);?></label>
				<div class="controls"><?php echo $app->experience;?></div>
			</div>
		</div>

		<div class="span4 offset1">
			<div class="control-group">
				<label class="control-label"><?php echo lang('short.hear_about_us.question', 2);?></label>
				<div class="controls">
					<p><?php echo $app->hear_about;?></p>
				</div>
			</div>

			<?php if ( ! empty($app->hear_about_detail)): ?>
				<div class="control-group">
					<label class="control-label"><?php echo lang('detail', 1);?></label>
					<div class="controls">
						<p><?php echo $app->hear_about_detail;?></p>
					</div>
				</div>
			<?php endif;?>
		</div>
	</div>
</div>

<hr>

<div class="row">
	<div class="span10">
		<textarea name="content" rows="5" class="span10" placeholder="Enter your comments on this application here"></textarea>
	</div>

	<div class="span2">
		<div class="btn-group">
			<a href="#" class="btn btn-small btn-success"><i class="icon-thumbs-up icon-white"></i></a>
			<a href="#" class="btn btn-small btn-danger"><i class="icon-thumbs-down icon-white"></i></a>
		</div><br>

		<div class="btn-group">
			<button class="btn"><?php echo lang('action.submit', 1);?></button>
		</div>
	</div>
</div>

<hr>

<ul class="nav nav-pills">
	<li class="active"><a href="#characterForm" data-toggle="pill"><?php echo lang('character bio', 2);?></a></li>
	
	<?php if (Sentry::user()->has_level('character.create', 2)): ?>
		<li><a href="#userForm" data-toggle="pill"><?php echo lang('user info', 2);?></a></li>
	<?php endif;?>
	
	<?php if ( ! empty($samplePost)): ?>
		<li><a href="#samplePost" data-toggle="pill"><?php echo lang('sample_post', 2);?></a></li>
	<?php endif;?>

	<li class="pull-right"><a href="#reviewHistory" data-toggle="pill"><?php echo lang('review history', 2);?></a></li>
</ul>

<div class="pill-content">
	<div id="characterForm" class="active pill-pane"><?php echo $characterForm;?></div>

	<?php if (Sentry::user()->has_level('character.create', 2)): ?>
		<div id="userForm" class="pill-pane"><?php echo $userForm;?></div>
	<?php endif;?>

	<?php if ( ! empty($samplePost)): ?>
		<div id="samplePost" class="pill-pane">
			<p><strong><?php echo $samplePost;?></strong></p>
			<?php echo Markdown::parse($app->sample_post);?>
		</div>
	<?php endif;?>

	<div id="reviewHistory" class="pill-pane">
		<?php if (count($responses) > 0): ?>
			<?php foreach ($responses as $res): ?>
				<?php if ($res->type == \Model_Application_Response::COMMENT): ?>
					<blockquote>
						<?php echo Markdown::parse($res->content);?>
						<small><?php echo $res->user->name;?></small>
					</blockquote>
				<?php elseif ($res->type == \Model_Application_Response::VOTE): ?>
					<?php if ($res->content == 'yes'): ?>
						<p class="alert alert-success"><?php echo lang('[[short.arc.voted|{{'.$res->user->name.'}}|yes]]');?></p>
					<?php else: ?>
						<p class="alert alert-danger"><?php echo lang('[[short.arc.voted|{{'.$res->user->name.'}}|no]]');?></p>
					<?php endif;?>
				<?php elseif ($res->type == \Model_Application_Response::RESPONSE): ?>
					<div class="alert alert-block alert-info">
						<h4 class="alert-heading"><?php echo lang('final response', 2);?></h4>
						<?php echo Markdown::parse($res->content);?>
					</div>
				<?php endif;?>
			<?php endforeach;?>
		<?php else: ?>
			<p class="alert"><?php echo lang('[[error.not_found|application review history]]');?></p>
		<?php endif;?>
	</div>
</div>