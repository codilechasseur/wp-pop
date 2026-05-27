/**
 * WP Pop! Block Editor Sidebar Panels.
 *
 * Registers multiple PluginDocumentSettingPanel sections for the wp_pop
 * post type, providing all targeting/appearance/behaviour settings in
 * the editor sidebar.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

import './index.css';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	TextControl as BaseTextControl,
	SelectControl as BaseSelectControl,
	ToggleControl,
	CheckboxControl,
	BaseControl,
	RangeControl as BaseRangeControl,
	TextareaControl,
	Button,
	PanelRow,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
const TextControl  = ( props ) => <BaseTextControl  __next40pxDefaultSize { ...props } />;
const SelectControl = ( props ) => <BaseSelectControl __next40pxDefaultSize { ...props } />;
const RangeControl  = ( props ) => <BaseRangeControl  __next40pxDefaultSize { ...props } />;
import { __, sprintf } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';

const {
	postTypes      = [],
	taxonomies     = [],
	userRoles      = [],
	wooActive      = false,
	wcPageOptions  = [],
	wcTriggerOptions = [],
	popupTypeOptions = [],
	triggerOptions   = [],
	frequencyOptions = [],
	animationOptions = [],
	deviceOptions    = [],
	visitorOptions   = [],
	loggedInOptions  = [],
} = window.wpPopEditor || {};

// ---- Hook helper -----------------------------------------------------------

function useMeta() {
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	function get( key, fallback = '' ) {
		return meta?.[ key ] ?? fallback;
	}

	function set( key ) {
		return ( value ) => setMeta( { ...meta, [ key ]: value } );
	}

	return { get, set };
}

// ---- Targeting Panel -------------------------------------------------------

function TargetingPanel() {
	const { get, set } = useMeta();

	// URL patterns stored as newline-separated string in a JSON array meta.
	const urlPatterns = ( () => {
		try { return JSON.parse( get( '_wp_pop_url_patterns', '[]' ) ); } catch { return []; }
	} )();

	function setUrlPatterns( arr ) {
		set( '_wp_pop_url_patterns' )( JSON.stringify( arr ) );
	}

	// Post types.
	const targetPostTypes = ( () => {
		try { return JSON.parse( get( '_wp_pop_target_post_types', '[]' ) ); } catch { return []; }
	} )();

	function setTargetPostTypes( arr ) {
		set( '_wp_pop_target_post_types' )( JSON.stringify( arr ) );
	}

	const rawRoles    = get( '_wp_pop_target_user_roles', [] );
	const selectedRoles = Array.isArray( rawRoles ) ? rawRoles : [];

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-targeting"
			title={ __( 'Targeting', 'wp-pop' ) }
			initialOpen={ false }
		>
			<SelectControl
				label={ __( 'Display on', 'wp-pop' ) }
				value={ get( '_wp_pop_display_scope', 'all' ) }
				options={ [
					{ value: 'all',      label: __( 'All pages', 'wp-pop' ) },
					{ value: 'home',     label: __( 'Homepage only', 'wp-pop' ) },
					{ value: 'singular', label: __( 'Singular posts/pages', 'wp-pop' ) },
					{ value: 'archive',  label: __( 'Archive pages', 'wp-pop' ) },
					{ value: 'selected', label: __( 'Selected post types', 'wp-pop' ) },
					{ value: 'custom',   label: __( 'URL patterns', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_display_scope' ) }
			/>

			{ 'selected' === get( '_wp_pop_display_scope' ) && (
				<PanelRow>
					<fieldset style={ { width: '100%' } }>
						<legend>{ __( 'Post types', 'wp-pop' ) }</legend>
						{ postTypes.map( ( pt ) => (
							<ToggleControl
								key={ pt.value }
								label={ pt.label }
								checked={ targetPostTypes.includes( pt.value ) }
								onChange={ ( checked ) => {
									const next = checked
										? [ ...targetPostTypes, pt.value ]
										: targetPostTypes.filter( ( v ) => v !== pt.value );
									setTargetPostTypes( next );
								} }
							/>
						) ) }
					</fieldset>
				</PanelRow>
			) }

			{ 'custom' === get( '_wp_pop_display_scope' ) && (
				<TextareaControl
					label={ __( 'URL patterns (one per line, supports * wildcard)', 'wp-pop' ) }
					value={ urlPatterns.join( '\n' ) }
					onChange={ ( val ) => setUrlPatterns( val.split( '\n' ).filter( Boolean ) ) }
				/>
			) }

			<SelectControl
				label={ __( 'Device', 'wp-pop' ) }
				value={ get( '_wp_pop_device', 'all' ) }
				options={ deviceOptions.length ? deviceOptions : [
					{ value: 'all',     label: __( 'All devices', 'wp-pop' ) },
					{ value: 'desktop', label: __( 'Desktop only', 'wp-pop' ) },
					{ value: 'mobile',  label: __( 'Mobile only', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_device' ) }
			/>

			<SelectControl
				label={ __( 'Visitor type', 'wp-pop' ) }
				value={ get( '_wp_pop_visitor_type', 'all' ) }
				options={ visitorOptions.length ? visitorOptions : [
					{ value: 'all',       label: __( 'All visitors', 'wp-pop' ) },
					{ value: 'new',       label: __( 'New visitors', 'wp-pop' ) },
					{ value: 'returning', label: __( 'Returning visitors', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_visitor_type' ) }
			/>

			<SelectControl
				label={ __( 'Login status', 'wp-pop' ) }
				value={ get( '_wp_pop_logged_in', 'all' ) }
				options={ loggedInOptions.length ? loggedInOptions : [
					{ value: 'all',    label: __( 'Everyone', 'wp-pop' ) },
					{ value: 'yes',    label: __( 'Logged-in users only', 'wp-pop' ) },
					{ value: 'no',     label: __( 'Logged-out visitors only', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_logged_in' ) }
			/>

			{ userRoles.length > 0 && (
				<BaseControl label={ __( 'Limit to user roles', 'wp-pop' ) } help={ __( 'Leave blank to allow all roles.', 'wp-pop' ) }>
					{ userRoles.map( ( role ) => (
						<CheckboxControl
							__nextHasNoMarginBottom
							key={ role.value }
							label={ role.label }
							checked={ selectedRoles.includes( role.value ) }
							onChange={ ( checked ) => {
								set( '_wp_pop_target_user_roles' )(
									checked
										? [ ...selectedRoles, role.value ]
										: selectedRoles.filter( ( r ) => r !== role.value )
								);
							} }
						/>
					) ) }
				</BaseControl>
			) }
		</PluginDocumentSettingPanel>
	);
}

// ---- Schedule Panel --------------------------------------------------------

function SchedulePanel() {
	const { get, set } = useMeta();

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-schedule"
			title={ __( 'Schedule', 'wp-pop' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __( 'Enable scheduling', 'wp-pop' ) }
				checked={ !! get( '_wp_pop_scheduling_enabled', false ) }
				onChange={ set( '_wp_pop_scheduling_enabled' ) }
			/>

			{ !! get( '_wp_pop_scheduling_enabled' ) && (
				<Fragment>
					<TextControl
						type="date"
						label={ __( 'Start date', 'wp-pop' ) }
						value={ get( '_wp_pop_start_date', '' ) }
						onChange={ set( '_wp_pop_start_date' ) }
					/>
					<TextControl
						type="time"
						label={ __( 'Start time (optional)', 'wp-pop' ) }
						value={ get( '_wp_pop_start_time', '' ) }
						onChange={ set( '_wp_pop_start_time' ) }
					/>
					<TextControl
						type="date"
						label={ __( 'End date', 'wp-pop' ) }
						value={ get( '_wp_pop_end_date', '' ) }
						onChange={ set( '_wp_pop_end_date' ) }
					/>
					<TextControl
						type="time"
						label={ __( 'End time (optional)', 'wp-pop' ) }
						value={ get( '_wp_pop_end_time', '' ) }
						onChange={ set( '_wp_pop_end_time' ) }
					/>
				</Fragment>
			) }
		</PluginDocumentSettingPanel>
	);
}

// ---- Trigger & Frequency Panel ---------------------------------------------

function TriggerFrequencyPanel() {
	const { get, set } = useMeta();
	const trigger   = get( '_wp_pop_trigger', 'time' );
	const frequency = get( '_wp_pop_frequency', 'always' );

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-trigger"
			title={ __( 'Trigger & Frequency', 'wp-pop' ) }
			initialOpen={ true }
		>
			<SelectControl
				label={ __( 'Trigger', 'wp-pop' ) }
				value={ trigger }
				options={ triggerOptions.length ? triggerOptions : [
					{ value: 'time',               label: __( 'Time delay', 'wp-pop' ) },
					{ value: 'scroll',             label: __( 'Scroll percentage', 'wp-pop' ) },
					{ value: 'exit_intent',        label: __( 'Exit intent', 'wp-pop' ) },
					{ value: 'click',              label: __( 'Click on element', 'wp-pop' ) },
					{ value: 'inactivity',         label: __( 'User inactivity', 'wp-pop' ) },
					{ value: 'element_visibility', label: __( 'Element in view', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_trigger' ) }
			/>

			{ ( 'time' === trigger || 'inactivity' === trigger ) && (
				<RangeControl
					label={ 'time' === trigger
						? __( 'Delay (seconds)', 'wp-pop' )
						: __( 'Inactivity timeout (seconds)', 'wp-pop' ) }
					value={ Number( get( 'time' === trigger ? '_wp_pop_trigger_delay' : '_wp_pop_inactivity_seconds', 0 ) ) }
					onChange={ set( 'time' === trigger ? '_wp_pop_trigger_delay' : '_wp_pop_inactivity_seconds' ) }
					min={ 0 }
					max={ 300 }
				/>
			) }

			{ 'scroll' === trigger && (
				<RangeControl
					label={ __( 'Scroll percentage', 'wp-pop' ) }
					value={ Number( get( '_wp_pop_scroll_threshold', 50 ) ) }
					onChange={ set( '_wp_pop_scroll_threshold' ) }
					min={ 1 }
					max={ 100 }
				/>
			) }

			{ ( 'click' === trigger || 'element_visibility' === trigger ) && (
				<TextControl
					label={ 'click' === trigger
						? __( 'CSS selector for click trigger', 'wp-pop' )
						: __( 'CSS selector to watch', 'wp-pop' ) }
					value={ get( 'click' === trigger ? '_wp_pop_click_selector' : '_wp_pop_element_selector', '' ) }
					onChange={ set( 'click' === trigger ? '_wp_pop_click_selector' : '_wp_pop_element_selector' ) }
					placeholder=".my-button"
				/>
			) }

			<hr />

			<SelectControl
				label={ __( 'Frequency', 'wp-pop' ) }
				value={ frequency }
				options={ frequencyOptions.length ? frequencyOptions : [
					{ value: 'always',  label: __( 'Every visit', 'wp-pop' ) },
					{ value: 'once',    label: __( 'Once per browser', 'wp-pop' ) },
					{ value: 'session', label: __( 'Once per session', 'wp-pop' ) },
					{ value: 'daily',   label: __( 'Once per day', 'wp-pop' ) },
					{ value: 'custom',  label: __( 'Custom interval', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_frequency' ) }
			/>

			{ 'custom' === frequency && (
				<TextControl
					type="number"
					label={ __( 'Retrigger interval (minutes)', 'wp-pop' ) }
					value={ get( '_wp_pop_retrigger_minutes', 60 ) }
					onChange={ ( val ) => set( '_wp_pop_retrigger_minutes' )( parseInt( val, 10 ) || 60 ) }
					min={ 1 }
				/>
			) }

			<TextControl
				type="number"
				label={ __( 'Priority (higher = shown first)', 'wp-pop' ) }
				value={ get( '_wp_pop_popup_priority', 10 ) }
				onChange={ ( val ) => set( '_wp_pop_popup_priority' )( parseInt( val, 10 ) || 10 ) }
				min={ 0 }
			/>

			<ToggleControl
				label={ __( 'Test mode (always show for admins)', 'wp-pop' ) }
				checked={ !! get( '_wp_pop_test_mode', false ) }
				onChange={ set( '_wp_pop_test_mode' ) }
			/>
		</PluginDocumentSettingPanel>
	);
}

// ---- Appearance Panel ------------------------------------------------------

function AppearancePanel() {
	const { get, set } = useMeta();

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-appearance"
			title={ __( 'Appearance', 'wp-pop' ) }
			initialOpen={ false }
		>
			<SelectControl
				label={ __( 'Popup type', 'wp-pop' ) }
				value={ get( '_wp_pop_popup_type', 'modal' ) }
				options={ popupTypeOptions.length ? popupTypeOptions : [
					{ value: 'modal',       label: __( 'Modal (center)', 'wp-pop' ) },
					{ value: 'top_bar',     label: __( 'Top bar', 'wp-pop' ) },
					{ value: 'bottom_bar',  label: __( 'Bottom bar', 'wp-pop' ) },
					{ value: 'slide_in_tl', label: __( 'Slide-in (top-left)', 'wp-pop' ) },
					{ value: 'slide_in_tr', label: __( 'Slide-in (top-right)', 'wp-pop' ) },
					{ value: 'slide_in_bl', label: __( 'Slide-in (bottom-left)', 'wp-pop' ) },
					{ value: 'slide_in_br', label: __( 'Slide-in (bottom-right)', 'wp-pop' ) },
					{ value: 'fullscreen',  label: __( 'Fullscreen overlay', 'wp-pop' ) },
					{ value: 'tooltip',     label: __( 'Tooltip / inline', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_popup_type' ) }
			/>

			<SelectControl
				label={ __( 'Animation', 'wp-pop' ) }
				value={ get( '_wp_pop_animation', 'fade' ) }
				options={ animationOptions.length ? animationOptions : [
					{ value: 'fade',       label: __( 'Fade', 'wp-pop' ) },
					{ value: 'slide_down', label: __( 'Slide down', 'wp-pop' ) },
					{ value: 'zoom',       label: __( 'Zoom', 'wp-pop' ) },
					{ value: 'none',       label: __( 'None', 'wp-pop' ) },
				] }
				onChange={ set( '_wp_pop_animation' ) }
			/>

			<TextControl
				label={ __( 'Width (e.g. 600px, 80%, 40em)', 'wp-pop' ) }
				value={ get( '_wp_pop_width', '600px' ) }
				onChange={ set( '_wp_pop_width' ) }
			/>

			<ToggleControl
				label={ __( 'Show overlay', 'wp-pop' ) }
				checked={ !! get( '_wp_pop_overlay', true ) }
				onChange={ set( '_wp_pop_overlay' ) }
			/>

			{ !! get( '_wp_pop_overlay', true ) && (
				<TextControl
					type="color"
					label={ __( 'Overlay color', 'wp-pop' ) }
					value={ get( '_wp_pop_overlay_color', '#000000' ) }
					onChange={ set( '_wp_pop_overlay_color' ) }
				/>
			) }

			<RangeControl
				label={ __( 'Border radius (px)', 'wp-pop' ) }
				value={ Number( get( '_wp_pop_border_radius', 4 ) ) }
				onChange={ set( '_wp_pop_border_radius' ) }
				min={ 0 }
				max={ 50 }
			/>

			<TextControl
				label={ __( 'Padding (CSS shorthand)', 'wp-pop' ) }
				value={ get( '_wp_pop_padding', '20px' ) }
				onChange={ set( '_wp_pop_padding' ) }
			/>

			<ToggleControl
				label={ __( 'Show close button', 'wp-pop' ) }
				checked={ !! get( '_wp_pop_show_close_button', true ) }
				onChange={ set( '_wp_pop_show_close_button' ) }
			/>

			{ !! get( '_wp_pop_show_close_button', true ) && (
				<Fragment>
					<TextControl
						type="color"
						label={ __( 'Close button color', 'wp-pop' ) }
						value={ get( '_wp_pop_close_color', '#333333' ) }
						onChange={ set( '_wp_pop_close_color' ) }
					/>
					<RangeControl
						label={ __( 'Auto-close delay (0 = never)', 'wp-pop' ) }
						value={ Number( get( '_wp_pop_close_delay', 0 ) ) }
						onChange={ set( '_wp_pop_close_delay' ) }
						min={ 0 }
						max={ 60 }
					/>
				</Fragment>
			) }

			<ToggleControl
				label={ __( 'Close on outside click', 'wp-pop' ) }
				checked={ !! get( '_wp_pop_close_on_outside_click', true ) }
				onChange={ set( '_wp_pop_close_on_outside_click' ) }
			/>

			<ToggleControl
				label={ __( 'Close with Escape key', 'wp-pop' ) }
				checked={ !! get( '_wp_pop_close_on_esc', true ) }
				onChange={ set( '_wp_pop_close_on_esc' ) }
			/>
		</PluginDocumentSettingPanel>
	);
}

// ---- WooCommerce Panel -----------------------------------------------------

function WooCommercePanel() {
	const { get, set } = useMeta();

	if ( ! wooActive ) return null;

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-woocommerce"
			title={ __( 'WooCommerce', 'wp-pop' ) }
			initialOpen={ false }
		>
			<SelectControl
				label={ __( 'WooCommerce page', 'wp-pop' ) }
				value={ get( '_wp_pop_wc_page', '' ) }
				options={ [ { value: '', label: __( '— None —', 'wp-pop' ) }, ...wcPageOptions ] }
				onChange={ set( '_wp_pop_wc_page' ) }
				help={ __( 'Show only on this WooCommerce page.', 'wp-pop' ) }
			/>

			<SelectControl
				label={ __( 'WooCommerce trigger', 'wp-pop' ) }
				value={ get( '_wp_pop_wc_trigger', '' ) }
				options={ [ { value: '', label: __( '— None —', 'wp-pop' ) }, ...wcTriggerOptions ] }
				onChange={ set( '_wp_pop_wc_trigger' ) }
			/>
		</PluginDocumentSettingPanel>
	);
}

// ---- Conversion Tracking Panel ---------------------------------------------

function ConversionTrackingPanel() {
	const { get, set } = useMeta();

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-conversion"
			title={ __( 'Conversion Tracking', 'wp-pop' ) }
			initialOpen={ false }
		>
			<TextControl
				label={ __( 'Success URL pattern', 'wp-pop' ) }
				help={ __( 'Optional. If visitors land on a URL matching this pattern after seeing the popup, a conversion is recorded. Supports * as wildcard (e.g. /thank-you*).', 'wp-pop' ) }
				value={ get( '_wp_pop_success_url', '' ) }
				onChange={ set( '_wp_pop_success_url' ) }
				placeholder="/thank-you*"
			/>
		</PluginDocumentSettingPanel>
	);
}

// ---- Geo Targeting Panel ---------------------------------------------------

function GeoTargetingPanel() {
	const { get, set } = useMeta();
	const enabled = !! get( '_wp_pop_geo_enabled', false );

	const parseList = ( key ) => {
		try { return JSON.parse( get( key, '[]' ) ); } catch { return []; }
	};

	const setList = ( key ) => ( text ) =>
		set( key )( JSON.stringify(
			text.split( '\n' ).map( ( s ) => s.trim() ).filter( Boolean )
		) );

	return (
		<PluginDocumentSettingPanel
			name="wp-pop-geo"
			title={ __( 'Geo Targeting', 'wp-pop' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __( 'Enable geo targeting', 'wp-pop' ) }
				checked={ enabled }
				onChange={ set( '_wp_pop_geo_enabled' ) }
			/>

			{ enabled && (
				<Fragment>
					<SelectControl
						label={ __( 'Mode', 'wp-pop' ) }
						value={ get( '_wp_pop_geo_mode', 'allow' ) }
						options={ [
							{ value: 'allow', label: __( 'Show only to matching locations', 'wp-pop' ) },
							{ value: 'block', label: __( 'Hide for matching locations', 'wp-pop' ) },
						] }
						onChange={ set( '_wp_pop_geo_mode' ) }
					/>

					<TextareaControl
						label={ __( 'Countries', 'wp-pop' ) }
						help={ __( 'One ISO country code per line (e.g. US, GB, DE). Leave blank to match any country.', 'wp-pop' ) }
						value={ parseList( '_wp_pop_geo_countries' ).join( '\n' ) }
						onChange={ setList( '_wp_pop_geo_countries' ) }
						rows={ 3 }
						placeholder={ 'US\nGB\nDE' }
					/>

					<TextareaControl
						label={ __( 'Regions / States', 'wp-pop' ) }
						help={ __( 'One region name per line, partial match (e.g. California). Leave blank to match any region.', 'wp-pop' ) }
						value={ parseList( '_wp_pop_geo_regions' ).join( '\n' ) }
						onChange={ setList( '_wp_pop_geo_regions' ) }
						rows={ 2 }
						placeholder={ 'California\nTexas' }
					/>

					<TextareaControl
						label={ __( 'Cities', 'wp-pop' ) }
						help={ __( 'One city name per line, partial match. Requires ipgeolocation.io or MaxMind — ip-api.com also provides city data on the free tier.', 'wp-pop' ) }
						value={ parseList( '_wp_pop_geo_cities' ).join( '\n' ) }
						onChange={ setList( '_wp_pop_geo_cities' ) }
						rows={ 2 }
						placeholder={ 'London\nNew York' }
					/>
				</Fragment>
			) }
		</PluginDocumentSettingPanel>
	);
}

// ---- Root component --------------------------------------------------------

function WpPopSettings() {
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);

	if ( 'wp_pop' !== postType ) return null;

	return (
		<Fragment>
			<TargetingPanel />
			<GeoTargetingPanel />
			<SchedulePanel />
			<TriggerFrequencyPanel />
			<AppearancePanel />
			{ wooActive && <WooCommercePanel /> }
			<ConversionTrackingPanel />
		</Fragment>
	);
}

registerPlugin( 'wp-pop-settings', {
	render: WpPopSettings,
} );
