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
			<table class="form">
				<tr>
					<td><?php echo $help_feed_url; ?></td>
					<td>
						<?php foreach ($feed_urls as $feed) { ?>
							<div style="margin-bottom:6px;">
								<strong><?php echo $feed['name']; ?> (<?php echo $feed['code']; ?>)</strong><br />
								<input type="text"
									value="<?php echo $feed['url']; ?>"
									style="width:100%;"
									readonly
									onclick="this.select();" />
							</div>
						<?php } ?>
					</td>
				</tr>
			</table>


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
						<td><?php echo $entry_filter_attributes; ?></td>
						<td>
							<div class="scrollbox" id="filter-attributes" style="height:200px;">
								<?php $class = 'odd'; ?>
								<?php if (!empty($attributes)) { ?>
									<?php foreach ($attributes as $a) { ?>
										<?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
										<div class="<?php echo $class; ?>">
											<?php if (in_array((int)$a['attribute_id'], $eleads_yml_filter_attributes)) { ?>
												<input type="checkbox" name="eleads_yml_filter_attributes[]" value="<?php echo (int)$a['attribute_id']; ?>" checked="checked" />
												<?php echo $a['name']; ?>
											<?php } else { ?>
												<input type="checkbox" name="eleads_yml_filter_attributes[]" value="<?php echo (int)$a['attribute_id']; ?>" />
												<?php echo $a['name']; ?>
											<?php } ?>
										</div>
									<?php } ?>
								<?php } ?>
							</div>
							<div style="margin-bottom:6px;">
								<a href="javascript:void(0);" class="btn-select-all" data-target="filter-attributes"><?php echo $text_select_all; ?></a>
								&nbsp; / &nbsp;
								<a href="javascript:void(0);" class="btn-unselect-all" data-target="filter-attributes"><?php echo $text_unselect_all; ?></a>
							</div>

							<div class="help"><?php echo $help_filter_attributes; ?></div>
						</td>
					</tr>

					<tr>
						<td>
							<?php echo $entry_filter_options; ?>
						</td>
						<td>
							<div class="scrollbox" id="filter-options" style="height:200px;">
								<?php $class = 'odd'; ?>
								<?php if (!empty($options)) { ?>
									<?php foreach ($options as $o) { ?>
										<?php $class = ($class == 'even' ? 'odd' : 'even'); ?>
										<div class="<?php echo $class; ?>">
											<?php if (in_array((int)$o['option_id'], $eleads_yml_filter_options)) { ?>
												<input type="checkbox" name="eleads_yml_filter_options[]" value="<?php echo (int)$o['option_id']; ?>" checked="checked" />
												<?php echo $o['name']; ?>
											<?php } else { ?>
												<input type="checkbox" name="eleads_yml_filter_options[]" value="<?php echo (int)$o['option_id']; ?>" />
												<?php echo $o['name']; ?>
											<?php } ?>
										</div>
									<?php } ?>
								<?php } ?>
							</div>
							<div style="margin-bottom:6px;">
								<a href="javascript:void(0);" class="btn-select-all" data-target="filter-options"><?php echo $text_select_all; ?></a>
								&nbsp; / &nbsp;
								<a href="javascript:void(0);" class="btn-unselect-all" data-target="filter-options"><?php echo $text_unselect_all; ?></a>
							</div>
							<div class="help"><?php echo $help_filter_options; ?></div>
						</td>
					</tr>

					<tr>
						<td><?php echo $entry_key; ?><br><span class="help"><?php echo $help_key; ?></span></td>
						<td><input type="text" name="eleads_yml_key" value="<?php echo $eleads_yml_key; ?>" style="width:300px" /></td>
					</tr>

					<tr><td><?php echo $entry_shop_name; ?></td><td><input type="text" name="eleads_yml_shop_name" value="<?php echo $eleads_yml_shop_name; ?>" style="width:300px" /></td></tr>
					<tr><td><?php echo $entry_email; ?></td><td><input type="text" name="eleads_yml_email" value="<?php echo $eleads_yml_email; ?>" style="width:300px" /></td></tr>
					<tr><td><?php echo $entry_url; ?></td><td><input type="text" name="eleads_yml_url" value="<?php echo $eleads_yml_url; ?>" style="width:420px" /></td></tr>
					<tr><td><?php echo $entry_currency; ?></td><td><input type="text" name="eleads_yml_currency" value="<?php echo $eleads_yml_currency; ?>" style="width:300px" /></td></tr>

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
<script type="text/javascript">
$('.btn-select-all').on('click', function(){
  var id = $(this).data('target');
  $('#' + id + ' input[type=checkbox]').prop('checked', true);
});

$('.btn-unselect-all').on('click', function(){
  var id = $(this).data('target');
  $('#' + id + ' input[type=checkbox]').prop('checked', false);
});
</script>
<?php echo $footer; ?>
