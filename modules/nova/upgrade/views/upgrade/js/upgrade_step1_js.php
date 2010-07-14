<?php

$specsarray = Database::instance()->list_columns('sms_specs', NULL, FALSE);
$specshash = md5(implode('', array_keys($specsarray)));

$tourarray = Database::instance()->list_columns('sms_tour', NULL, FALSE);
$tourhash = md5(implode('', array_keys($tourarray)));

?><script type="text/javascript" src="<?php echo url::base().MODFOLDER;?>/assets/js/jquery.ajaxq.js"></script>
<script type="text/javascript" src="<?php echo url::base().MODFOLDER;?>/assets/js/jquery.ui.position.min.js"></script>
<script type="text/javascript" src="<?php echo url::base().MODFOLDER;?>/assets/js/jquery.ui.tooltip.min.js"></script>

<link rel="stylesheet" href="<?php echo url::base().MODFOLDER;?>/assets/css/jquery.ui.tooltip.css" />

<script type="text/javascript">
	$(document).ready(function(){
		var specshash = '<?php echo $specshash;?>';
		var tourhash = '<?php echo $tourhash;?>';
		
		$("[title]").tooltip();
		
		$("#progress").progressbar({ value: 25 });
		$('#percent').text($('#progress').progressbar('option', 'value') + '%');
		
		$('table.zebra tbody > tr:nth-child(odd)').addClass('alt');
		
		$('#next').live('click', function(){
			$('.lower').fadeOut('fast');
			$('#loaded').fadeOut('fast', function(){
				$('#loading').removeClass('hidden');
			});
		});
		
		if (specshash != 'e0027b9e9dd498d4c01b0ffd4e186646')
		{
			$('input[name=upgrade_specs]:nth(1)').attr("checked", true);
			$('input[name=upgrade_specs]').attr("disabled", "disabled");
		}
		
		if (tourhash != 'a03805aeed42e0c41f9f6f25c757e099')
		{
			$('input[name=upgrade_tour]:nth(1)').attr("checked", true);
			$('input[name=upgrade_tour]').attr("disabled", "disabled");
		}
		
		$('input[name=upgrade_characters_users]').change(function(){
			var checked = $('input[name=upgrade_characters_users]:checked').val();
			
			if (checked == 0)
			{
				$('input[name=upgrade_logs]:nth(1)').attr("checked", true);
				$('input[name=upgrade_logs]').attr("disabled", "disabled");
				
				$('input[name=upgrade_news]:nth(1)').attr("checked", true);
				$('input[name=upgrade_news]').attr("disabled", "disabled");
				
				$('input[name=upgrade_missions]:nth(1)').attr("checked", true);
				$('input[name=upgrade_missions]').attr("disabled", "disabled");
			}
			
			if (checked == 1)
			{
				$('input[name=upgrade_logs]:nth(0)').attr("checked", true);
				$('input[name=upgrade_logs]').attr("disabled", "");
				
				$('input[name=upgrade_news]:nth(0)').attr("checked", true);
				$('input[name=upgrade_news]').attr("disabled", "");
				
				$('input[name=upgrade_missions]:nth(0)').attr("checked", true);
				$('input[name=upgrade_missions]').attr("disabled", "");
			}
		});
		
		$('#start').live('click', function(){
			// characters and users
			if ($('input[name=upgrade_characters_users]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(0) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_characters');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(0) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(0) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(0) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(0) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(0) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(0) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// awards
			if ($('input[name=upgrade_awards]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(1) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_awards');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(1) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(1) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(1) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(1) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// settings and messages
			if ($('input[name=upgrade_settings]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(2) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_settings');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(2) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(2) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(2) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(2) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(2) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(2) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// personal logs
			if ($('input[name=upgrade_logs]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(3) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_logs');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(3) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(3) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(3) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(3) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(3) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(3) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// news categories and items
			if ($('input[name=upgrade_news]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(4) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_news');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(4) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(4) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(4) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(4) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(4) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(4) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// missions and mission posts
			if ($('input[name=upgrade_missions]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(5) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_missions');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(5) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(5) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(5) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(5) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(5) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(5) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// specs
			if ($('input[name=upgrade_specs]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(6) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_specs');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(6) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(6) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(6) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(6) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(6) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(6) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			// tour items
			if ($('input[name=upgrade_tour]:checked').val() == 1)
			{
				var send;
				
				$.ajaxq('queue', {
					beforeSend: function(){
						$('table tbody tr:eq(7) td:eq(1) .loading').removeClass('hidden');
					},
					type: "POST",
					url: "<?php echo url::site('upgradeajax/upgrade_tour');?>",
					data: send,
					dataType: 'json',
					success: function(data){
						$('table tbody tr:eq(7) td:eq(1) .loading').addClass('hidden');
						
						if (data.code == 1)
						{
							$('table tbody tr:eq(7) td:eq(1) .success').removeClass('hidden');
						}
						else if (data.code == 0)
						{
							$('table tbody tr:eq(7) td:eq(1) .failure').removeClass('hidden');
							$('table tbody tr:eq(7) td:eq(1) .failure img').attr('title', function(){
								return data.message
							});
						}
						else if (data.code == 2)
						{
							$('table tbody tr:eq(7) td:eq(1) .warning').removeClass('hidden');
							$('table tbody tr:eq(7) td:eq(1) .warning img').attr('title', function(){
								return data.message
							});
						}
					}
				});
			}
			
			$('#progress').ajaxStop(function(){
				$("#progress").progressbar({ value: 50 });
				$('#percent').text($('#progress').progressbar('option', 'value') + '%');
				
				// change the button and text
				$('.lower .control button').attr('id', 'next').html('<?php echo __("Next Step");?>');
				$('.lower .control-text').html('<?php echo __("Move on to the next step of the upgrade process.");?>');
			});
			
			return false;
		});
	});
</script>