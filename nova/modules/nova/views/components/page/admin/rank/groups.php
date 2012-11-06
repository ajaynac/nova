<div class="btn-toolbar">
	<div class="btn-group">
		<a href="<?php echo Uri::create('admin/rank/index');?>" class="btn icn16 tooltip-top" title="<?php echo lang('ranks index', 1);?>"><div class="icn icn-75" data-icon="<"></div></a>
	</div>

	<div class="btn-group">
		<a href="<?php echo Uri::create('admin/rank/info');?>" class="btn icn16 tooltip-top" title="<?php echo lang('action.edit rank info', 1);?>"><div class="icn icn-75" data-icon="i"></div></a>
		<a href="<?php echo Uri::create('admin/rank/manage');?>" class="btn icn16 tooltip-top" title="<?php echo lang('action.edit ranks', 1);?>"><div class="icn icn-75" data-icon="^"></div></a>
	</div>
</div>

<form method="post">
	<?php echo Form::hidden('action', 'create');?>
	<?php echo Form::hidden(Config::get('security.csrf_token_key'), Security::fetch_token());?>
	
	<div class="control-group">
		<label class="control-label"><?php echo lang('action.add rank group', 2);?></label>
		<div class="controls">
			<div class="input-append">
				<input type="text" name="name" class="span4"><button class="btn icn16"><div class="icn icn-50" data-icon="+"></div></button>
			</div>
		</div>
	</div>
</form>

<?php if (count($groups) > 0): ?>
	<table width="100%" class="table-striped sort-rankgroups">
		<tbody class="sort-body">
		<?php foreach ($groups as $g): ?>
			<tr height="40" id="group_<?php echo $g->id;?>">
				<td>
					<?php if ($g->status === Status::INACTIVE): ?>
						<span class="label label-important"><?php echo lang('off', 1);?></span>
					<?php endif;?>
					<?php echo $g->name;?>
				</td>
				<td class="span2">
					<div class="btn-toolbar">
						<div class="btn-group">
							<a href="<?php echo Uri::create('admin/rank/groups/'.$g->id);?>" class="btn btn-mini tooltip-top rankgroup-action" title="<?php echo lang('action.edit', 1);?>" data-action="update" data-id="<?php echo $g->id;?>"><div class="icn icn-50" data-icon="p"></div></a>
							<a href="<?php echo Uri::create('admin/rank/groups/'.$g->id);?>" class="btn btn-mini tooltip-top rankgroup-action" title="<?php echo lang('action.duplicate', 1);?>" data-action="duplicate" data-id="<?php echo $g->id;?>"><div class="icn icn-50" data-icon="_"></div></a>
						</div>

						<?php if (Sentry::user()->hasAccess('rank.delete')): ?>
							<div class="btn-group">
								<a href="<?php echo Uri::create('admin/rank/groups');?>" class="btn btn-danger btn-mini tooltip-top rankgroup-action" title="<?php echo lang('action.delete', 1);?>" data-action="delete" data-id="<?php echo $g->id;?>"><div class="icn icn-50" data-icon="x"></div></a>
							</div>
						<?php endif;?>
					</div>
				</td>
				<td class="span1 reorder"></td>
			</tr>
		<?php endforeach;?>
		</tbody>
	</table>
<?php else: ?>
	<p class="alert"><?php echo lang('[[error.not_found|rank groups]]');?></p>
<?php endif;?>