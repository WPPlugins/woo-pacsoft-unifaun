<div class="wrap">
	<h1>{{ title }}</h1>
	
	{{# tabs.length }}
	<div class="nav-tab-wrapper">
		{{# tabs }}
		<a class="nav-tab" href="options-general.php?page=fortnox&tab={{ name }}">{{ title }}</a>
		{{/ tabs }}
	</div>
	{{/ tabs.length }}
	
	<form method="post" action="options.php">
		{{{ hidden }}}
		{{# sections }}
			{{# section.title }}
				<h2 class="title">{{ . }}</h2>
			{{/ section.title }}
			
			{{# section.description }}
				<p>{{{ . }}}</p>
			{{/ section.description }}
			
			<table class="form-table">
				<tbody>
					{{# section.fields }}
					<tr>
						<th scope="row">{{ field.title }}</th>
						<td>
							{{# field.text }}
								<input type="text" name="{{ field.name }}" value="{{ field.value }}" class="{{ field.class }}" autocomplete="off" placeholder="{{ field.placeholder }}">
							{{/ field.text }}
							
							{{# field.password }}
								<input type="password" name="{{ field.name }}" value="{{ field.value }}" class="{{ class }}" autocomplete="off" placeholder="{{ field.placeholder }}">
							{{/ field.password }}
							
							{{# field.dropdown }}
								<select name="{{ field.name }}" class="{{ field.class }}">
									{{# field.options }}
										<option value="{{ option.value }}" {{# option.selected }}selected="selected"{{/ option.selected }}>{{ option.label }}</option>
									{{/ field.options }}
								</select>
							{{/ field.dropdown }}
							
							{{# field.radio }}
								{{# field.options }}
									<p>
										<label>
											<input type="radio" name="{{ field.name }}" value="{{ option.value }}" {{# option.checked }}checked="checked"{{/ option.checked }} class="{{ option.class }}"> {{{ option.label }}}
										</label>
									</p>
								{{/ field.options }}
							{{/ field.radio }}
							
							{{# field.checkbox }}
								<p>
									<input type="hidden" name="{{ field.name }}" value="0">
									<label>
										<input type="checkbox" name="{{ field.name }}" value="1" {{# field.checked }}checked="checked"{{/ field.checked }} class="{{ class }}"> {{{ field.label }}}
									</label>
								</p>
							{{/ field.checkbox }}
							
							{{# field.checkboxes }}
								<fieldset>
									<legend class="screen-reader-text">
										<span>{{ field.title }}</span>
									</legend>
									
									{{# field.options }}
										<label>
											<input type="hidden" name="{{ option.name }}" value="0">
											<input type="checkbox" name="{{ option.name }}" value="1" {{# option.checked }}checked="checked"{{/ option.checked }} class="{{ option.class }}"> {{{ option.label }}}
										</label><br>
									{{/ field.options }}
								</fieldset>
							{{/ field.checkboxes }}
							
							{{# field.table }}
								<table class="wp-list-table widefat fixed striped posts {{ class }}">
									<thead>
										<tr>
											{{# table.columns }}
											<th class="column-{{ column.name }}">{{{ column.title }}}</th>
											{{/ table.columns }}
										</tr>
									</thead>
									<tbody {{# table.id }}id="{{ . }}"{{/ table.id }}>
										{{# table.rows }}
										{{> admin/settings/table-row }}
										{{/ table.rows }}
									</tbody>
								</table>
								
								{{# table.addRowButton }}
								<p><a href="#" class="button {{ table.addRowButtonClass }}">Add row</a></p>
								{{/ table.addRowButton }}
							{{/ field.table }}
							
							{{# field.button }}
							<a href="#" class="button button-primary button-hero fortnox-bulk-action" {{# field.data }}data-{{ key }}="{{ value }}" {{/ field.data }}style="text-align: center; width: 240px">{{ field.button.text }}</a> <span class="spinner fortnox-spinner hero"></span>
							{{/ field.button }}
							
							{{# field.description }}
								<p class="description">{{{ . }}}</p>
							{{/ field.description }}
						</td>
					</tr>
					{{/ section.fields }}
				</tbody>
			</table>
		{{/ sections }}
		
		<p class="submit">
			<button class="button-primary">{{# i18n }}Save changes{{/ i18n }}</button>
		</p>
	</form>
</div>
