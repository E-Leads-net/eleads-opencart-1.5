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
						<td><?php echo $entry_categories; ?></td>
						<td>
							<div class="scrollbox" style="height: 220px;">
								<?php $class = 'odd'; ?>
								<?php foreach ($categories as $category) { ?>
									<?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
									<div class="<?php echo $class; ?>">
										<?php if (in_array($category['category_id'], $eleads_yml_categories)) { ?>
											<input type="checkbox"
												   name="eleads_yml_categories[]"
												   value="<?php echo $category['category_id']; ?>"
												   checked="checked" />
											<?php echo $category['name']; ?>
										<?php } else { ?>
											<input type="checkbox"
												   name="eleads_yml_categories[]"
												   value="<?php echo $category['category_id']; ?>" />
											<?php echo $category['name']; ?>
										<?php } ?>
									</div>
								<?php } ?>
							</div>

							<a onclick="$(this).parent().find(':checkbox').attr('checked', true);"><?php echo $text_select_all; ?></a>
							/
							<a onclick="$(this).parent().find(':checkbox').attr('checked', false);"><?php echo $text_unselect_all; ?></a>

							<div class="help"><?php echo $help_categories; ?></div>
						</td>
					</tr>


					<tr>
						<td><?php echo $entry_key; ?><br><span class="help"><?php echo $help_key; ?></span></td>
						<td><input type="text" name="eleads_yml_key" value="<?php echo $eleads_yml_key; ?>" style="width:300px" /></td>
					</tr>

					<tr><td><?php echo $entry_shop_name; ?></td><td><input type="text" name="eleads_yml_shop_name" value="<?php echo $eleads_yml_shop_name; ?>" style="width:300px" /></td></tr>
					<tr><td><?php echo $entry_email; ?></td><td><input type="text" name="eleads_yml_email" value="<?php echo $eleads_yml_email; ?>" style="width:300px" /></td></tr>
					<tr><td><?php echo $entry_url; ?></td><td><input type="text" name="eleads_yml_url" value="<?php echo $eleads_yml_url; ?>" style="width:420px" /></td></tr>

					<tr><td><?php echo $entry_pictures_limit; ?></td><td><input type="text" name="eleads_yml_pictures_limit" value="<?php echo $eleads_yml_pictures_limit; ?>" style="width:120px" /></td></tr>

					<tr>
						<td><?php echo $entry_short_source; ?></td>
						<td>
							<select name="eleads_yml_short_source">
								<option value="meta_description" <?php echo ($eleads_yml_short_source=='meta_description' ? 'selected' : ''); ?>>meta_description</option>
								<option value="description" <?php echo ($eleads_yml_short_source=='description' ? 'selected' : ''); ?>>description</option>
							</select>
						</td>
					</tr>

				</table>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
