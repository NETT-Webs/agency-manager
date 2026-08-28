import apiFetch from '@wordpress/api-fetch';

const config = window.amAdminApp || {};

if ( config.restUrl ) {
	apiFetch.use( apiFetch.createRootURLMiddleware( config.restUrl ) );
}
if ( config.restNonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( config.restNonce ) );
}

const NAMESPACE = 'agency-manager/v1';

export function getDashboardStats() {
	return apiFetch( { path: `/${ NAMESPACE }/dashboard/stats` } );
}

export function getRecentActivity() {
	return apiFetch( { path: `/${ NAMESPACE }/dashboard/recent-activity` } );
}

export function getShortcodeGroups() {
	return apiFetch( { path: `/${ NAMESPACE }/shortcodes` } );
}

export function getApplications( type ) {
	return apiFetch( { path: `/${ NAMESPACE }/applications?type=${ type }` } );
}

export function setApplicationStatus( id, status ) {
	return apiFetch( { path: `/${ NAMESPACE }/applications/${ id }/status`, method: 'POST', data: { status } } );
}

export function publishApplication( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/applications/${ id }/publish`, method: 'POST' } );
}

export function getForms() {
	return apiFetch( { path: `/${ NAMESPACE }/forms` } );
}

export function getFormTemplates() {
	return apiFetch( { path: `/${ NAMESPACE }/forms/templates` } );
}

export function createForm( data ) {
	return apiFetch( { path: `/${ NAMESPACE }/forms`, method: 'POST', data } );
}

export function getFormBuilderData( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/forms/${ id }/builder` } );
}

// Saving still goes through the original admin-ajax endpoint
// (Form_Builder_Ajax::ajax_save()) unchanged, not the REST API — same nonce
// contract the classic Form Builder page used, so its business logic
// (whitelisted field sanitization, custom-field auto-registration, etc.)
// never has to be duplicated or reimplemented here.
export function saveFormSchema( { formId, title, formType, confirmation, fields } ) {
	const cfg = config.formBuilder || {};
	const body = new URLSearchParams();
	body.set( 'action', 'am_save_form_schema' );
	body.set( 'nonce', cfg.nonce || '' );
	body.set( 'form_id', formId );
	body.set( 'title', title );
	body.set( 'form_type', formType );
	body.set( 'confirmation_message', confirmation );
	body.set( 'fields', JSON.stringify( fields ) );

	return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } )
		.then( ( r ) => r.json() )
		.then( ( json ) => {
			if ( ! json || ! json.success ) {
				const message = ( json && json.data && json.data.message ) || 'Could not save the form.';
				throw new Error( message );
			}
			return json.data;
		} );
}

export function duplicateForm( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/forms/${ id }/duplicate`, method: 'POST' } );
}

export function deleteForm( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/forms/${ id }`, method: 'DELETE' } );
}

export function getTalentList() {
	return apiFetch( { path: `/${ NAMESPACE }/talent` } );
}

export function getLocationsList() {
	return apiFetch( { path: `/${ NAMESPACE }/locations` } );
}

// wp_localize_script stringifies every top-level scalar, so config.recordId
// arrives as e.g. "0" or "22" (never a number) — "0" is truthy in JS, so a
// plain `||` fallback would misclassify the Add screen as editing record 0.
export const recordId = parseInt( config.recordId, 10 ) || 0;

// Same wp_localize_script stringification caveat as recordId above.
export const formBuilderId = parseInt( ( config.formBuilder || {} ).formId, 10 ) || 0;

function profileApi( base ) {
	return {
		getOptions: () => apiFetch( { path: `/${ NAMESPACE }/${ base }/options` } ),
		get: ( id ) => apiFetch( { path: `/${ NAMESPACE }/${ base }/${ id }` } ),
		create: ( data ) => apiFetch( { path: `/${ NAMESPACE }/${ base }`, method: 'POST', data } ),
		update: ( id, data ) => apiFetch( { path: `/${ NAMESPACE }/${ base }/${ id }`, method: 'PUT', data } ),
		remove: ( id ) => apiFetch( { path: `/${ NAMESPACE }/${ base }/${ id }`, method: 'DELETE' } ),
		preview: ( id ) => apiFetch( { path: `/${ NAMESPACE }/${ base }/${ id }/preview` } ),
	};
}

export const talentApi = profileApi( 'talent' );
export const locationApi = profileApi( 'locations' );

export function getDisplaySettings() {
	return apiFetch( { path: `/${ NAMESPACE }/display-settings` } );
}

export function saveDisplaySettings( data ) {
	return apiFetch( { path: `/${ NAMESPACE }/display-settings`, method: 'POST', data } );
}

export function getSettings() {
	return apiFetch( { path: `/${ NAMESPACE }/settings` } );
}

export function saveSettings( data ) {
	return apiFetch( { path: `/${ NAMESPACE }/settings`, method: 'POST', data } );
}

export function getCsvImportFields( type ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/fields?type=${ type }` } );
}

export function uploadCsv( type, file ) {
	const body = new FormData();
	body.append( 'file', file );
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/upload?type=${ type }`, method: 'POST', body } );
}

export function getCsvSession( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/session/${ id }` } );
}

export function cancelCsvSession( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/session/${ id }`, method: 'DELETE' } );
}

export function saveCsvMapping( id, columnMap, options ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/session/${ id }/mapping`, method: 'POST', data: { columnMap, options } } );
}

export function previewCsvBatch( id, offset, limit ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/session/${ id }/preview`, method: 'POST', data: { offset, limit } } );
}

export function runCsvBatch( id, offset, limit ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/session/${ id }/run`, method: 'POST', data: { offset, limit } } );
}

export function getCsvMappingTemplates( type ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/mapping-templates?type=${ type }` } );
}

export function saveCsvMappingTemplate( name, type, columnMap ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/mapping-templates`, method: 'POST', data: { name, type, columnMap } } );
}

export function renameCsvMappingTemplate( id, name ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/mapping-templates/${ id }`, method: 'PUT', data: { name } } );
}

export function deleteCsvMappingTemplate( id ) {
	return apiFetch( { path: `/${ NAMESPACE }/csv-import/mapping-templates/${ id }`, method: 'DELETE' } );
}

export const links = config.links || {};
export const currentUser = config.currentUser || {};
export const screen = config.screen || 'dashboard';
