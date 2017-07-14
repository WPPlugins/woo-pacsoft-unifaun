<div id="pacsoft-sync-options-dialog">
	<h3>Skicka order <span class="pacsoft-order-id"></span></h3>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">{{ i18n.selectPacsoftService }}</th>
			<td>
				<select class="pacsoft-services">
					<option value=""></option>
					{{# services }}
					<option value="{{ code }}">{{ title }}</option>
					{{/ services }}
				</select>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<a href="#" class="button syncPacsoftOrderWithOptions">{{ i18n.syncOrder }}</a>
	</p>
</div>