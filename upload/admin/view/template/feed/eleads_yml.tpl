<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>

	<?php if ($error_warning) { ?>
		<div class="warning"><?php echo $error_warning; ?></div>
	<?php } ?>

	<div class="box">
		<div class="heading">
			<h1><?php echo $heading_title; ?></h1>
			<div class="buttons">
				<a onclick="$('#form').submit();" class="button"><?php echo $this->language->get('button_save'); ?></a>
				<a href="<?php echo $cancel; ?>" class="button"><?php echo $this->language->get('button_cancel'); ?></a>
			</div>
		</div>

		<div class="content">
			<div style="margin-bottom:10px; padding:10px; background:#f7f7f7; border:1px solid #ddd;">
				<b><?php echo $help_feed_url; ?></b>
				<code><?php echo $feed_url; ?></code>
			</div>

			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">

				<table class="form">
					<tr>
						<td><?php echo $entry_status; ?></td>
						<td>
							<select name="eleads_yml_status">
								<?php if ($eleads_yml_status) { ?>
									<option value="1" selected="selected"><?php echo $text_enabled; ?></option>
									<option value="0"><?php echo $text_disabled; ?></option>
								<?php } else { ?>
									<option value="1"><?php echo $text_enabled; ?></option>
									<option value="0" selected="selected"><?php echo $text_disabled; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>

					<tr>
						<td><?php echo $entry_key; ?><br><span class="help"><?php echo $help_key; ?></span></td>
						<td><input type="text" name="eleads_yml_key" value="<?php echo $eleads_yml_key; ?>" style="width:300px" /></td>
					</tr>

					<tr><td><?php echo $entry_agency; ?></td><td><input type="text" name="eleads_yml_agency" value="<?php echo $eleads_yml_agency; ?>" style="width:300px" /></td></tr>
					<tr><td><?php echo $entry_email; ?></td><td><input type="text" name="eleads_yml_email" value="<?php echo $eleads_yml_email; ?>" style="width:300px" /></td></tr>
					<tr><td><?php echo $entry_url; ?></td><td><input type="text" name="eleads_yml_url" value="<?php echo $eleads_yml_url; ?>" style="width:420px" /></td></tr>

					<tr><td><?php echo $entry_pictures_limit; ?></td><td><input type="text" name="eleads_yml_pictures_limit" value="<?php echo $eleads_yml_pictures_limit; ?>" style="width:120px" /></td></tr>

					<tr>
						<td><?php echo $entry_export_description; ?></td>
						<td>
							<select name="eleads_yml_export_description">
								<option value="1" <?php echo ($eleads_yml_export_description ? 'selected' : ''); ?>><?php echo $text_yes; ?></option>
								<option value="0" <?php echo (!$eleads_yml_export_description ? 'selected' : ''); ?>><?php echo $text_no; ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td><?php echo $entry_export_short_description; ?></td>
						<td>
							<select name="eleads_yml_export_short_description">
								<option value="1" <?php echo ($eleads_yml_export_short_description ? 'selected' : ''); ?>><?php echo $text_yes; ?></option>
								<option value="0" <?php echo (!$eleads_yml_export_short_description ? 'selected' : ''); ?>><?php echo $text_no; ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td><?php echo $entry_short_source; ?></td>
						<td>
							<select name="eleads_yml_short_source">
								<option value="meta_description" <?php echo ($eleads_yml_short_source=='meta_description' ? 'selected' : ''); ?>>meta_description</option>
								<option value="description" <?php echo ($eleads_yml_short_source=='description' ? 'selected' : ''); ?>>description</option>
							</select>
						</td>
					</tr>

					<tr>
						<td><?php echo $entry_price_mode; ?></td>
						<td>
							<select name="eleads_yml_price_mode">
								<option value="base_only" <?php echo ($eleads_yml_price_mode=='base_only' ? 'selected' : ''); ?>><?php echo $text_price_mode_base; ?></option>
								<option value="special_as_price" <?php echo ($eleads_yml_price_mode=='special_as_price' ? 'selected' : ''); ?>><?php echo $text_price_mode_special; ?></option>
							</select>
						</td>
					</tr>

				</table>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
