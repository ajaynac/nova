<br>
<div class="btn-toolbar">
	<div class="btn-group">
		<a href="<?php echo Uri::create('admin/rank/index');?>" class="btn tooltip-top" title="<?php echo lang('ranks index', 1);?>"><i class="icon-chevron-left icon-75"></i></a>
		<a href="<?php echo Uri::create('admin/rank/manage/0');?>" class="btn tooltip-top" title="<?php echo lang('action.create rank', 1);?>"><i class="icon-plus icon-75"></i></a>
	</div>

	<div class="btn-group">
		<a href="<?php echo Uri::create('admin/rank/info');?>" class="btn tooltip-top" title="<?php echo lang('action.edit rank info', 1);?>"><?php echo $images['info'];?></a>
		<a href="<?php echo Uri::create('admin/ranks/groups');?>" class="btn tooltip-top" title="<?php echo lang('action.edit rank groups', 1);?>"><?php echo $images['groups'];?></a>
	</div>

	<div class="btn-group pull-right">
		<form method="post">
			<input type="hidden" name="action" value="changeSet">
			<div class="control-group">
				<div class="controls">
					<div class="input-prepend input-append">
						<span class="add-on font-small"><?php echo lang('action.change rank set', 2);?></span><?php echo Form::select('changeSet', $default, $sets);?><button type="submit" class="btn"><?php echo lang('action.submit', 1);?></button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<br>

<?php if (count($groups) > 0): ?>
	<div class="tabbable tabs-left">
		<ul class="nav nav-tabs">
		<?php foreach ($groups as $g): ?>
			<li><a href="#group<?php echo $g->id;?>" data-toggle="tab"><?php echo $g->name;?></a></li>
		<?php endforeach;?>
		</ul>

		<div class="tab-content">
		<?php foreach ($groups as $g): ?>
			<div id="group<?php echo $g->id;?>" class="tab-pane">
				<?php if (count($g->ranks) > 0): ?>
					<table class="table table-striped">
						<tbody>
						<?php foreach ($g->ranks as $r): ?>
							<tr>
								<td class="span3"><?php echo $r->info->name;?></td>
								<td class="span3"><?php echo Location::rank($r->base, $r->pip, $default);?></td>
								<td class="span4"></td>
								<td class="span2">
									<div class="btn-toolbar">
										<div class="btn-group">
											<a href="<?php echo Uri::create('admin/rank/manage/'.$r->id);?>" class="btn btn-mini tooltip-top" title="<?php echo lang('action.edit', 1);?>"><i class="icon-pencil icon-75"></i></a>
										</div>

										<?php if (Sentry::user()->has_access('rank.delete')): ?>
											<div class="btn-group">
												<a href="#" class="btn btn-danger btn-mini tooltip-top rank-action" title="<?php echo lang('action.delete', 1);?>" data-action="delete" data-id="<?php echo $r->id;?>"><i class="icon-remove icon-white icon-50"></i></a>
											</div>
										<?php endif;?>
									</div>
								</td>
							</tr>
						<?php endforeach;?>
						</tbody>
					</table>
				<?php else: ?>
					<p class="alert"><?php echo lang('[[error.not_found|ranks]]');?></p>
				<?php endif;?>
			</div>
		<?php endforeach;?>
		</div>
	</div>
<?php else: ?>
	<p class="alert"><?php echo lang('[[error.not_found|rank groups]]');?></p>
<?php endif;?>