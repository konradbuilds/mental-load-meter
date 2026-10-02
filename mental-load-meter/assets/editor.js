/**
 * Mental Load Meter — editor panel.
 *
 * Plain JS through the wp.* globals. No JSX, no build step.
 *
 * @since 1.0.0
 */
( function ( wp, mlmData ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;

	// wp.editor since WP 6.6, wp.editPost before that. One codebase, feature detected.
	var host = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) ? wp.editor : wp.editPost;

	if ( ! host || ! host.PluginDocumentSettingPanel ) {
		return;
	}

	var PluginDocumentSettingPanel = host.PluginDocumentSettingPanel;

	/**
	 * Counts words in the current post content.
	 *
	 * @param {string} content Raw post content.
	 * @return {number} Word count.
	 */
	function countWords( content ) {
		var text = String( content || '' )
			.replace( /<!--[\s\S]*?-->/g, ' ' )
			.replace( /<[^>]*>/g, ' ' )
			.replace( /\s+/g, ' ' )
			.trim();

		if ( '' === text ) {
			return 0;
		}

		return text.split( ' ' ).length;
	}

	/**
	 * The sidebar panel.
	 *
	 * @return {Object|null} Panel element, or null outside supported post types.
	 */
	function MentalLoadPanel() {
		var editorData = useSelect( function ( select ) {
			var store = select( 'core/editor' );
			var meta = store.getEditedPostAttribute( 'meta' ) || {};

			return {
				postType: store.getCurrentPostType(),
				level: meta._mlm_level,
				minutes: meta._mlm_minutes,
				content: store.getEditedPostContent()
			};
		}, [] );

		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( -1 === mlmData.postTypes.indexOf( editorData.postType ) ) {
			return null;
		}

		var estimate = Math.max( 1, Math.ceil( countWords( editorData.content ) / mlmData.wpm ) );

		var options = [ { value: 0, label: __( 'Not set', 'mental-load-meter' ) } ].concat( mlmData.levels );

		/**
		 * Saves a meta value.
		 *
		 * @param {string} key   Meta key.
		 * @param {number} value Meta value.
		 */
		function setMeta( key, value ) {
			var meta = {};
			meta[ key ] = value;
			editPost( { meta: meta } );
		}

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'mental-load-meter',
				title: __( 'Mental load', 'mental-load-meter' )
			},
			el( SelectControl, {
				label: __( 'How heavy is it?', 'mental-load-meter' ),
				value: editorData.level || 0,
				options: options,
				help: __( 'Readers see the wording, not the number.', 'mental-load-meter' ),
				onChange: function ( value ) {
					setMeta( '_mlm_level', parseInt( value, 10 ) || 0 );
				}
			} ),
			el( TextControl, {
				label: __( 'Reading time, minutes', 'mental-load-meter' ),
				type: 'number',
				min: 0,
				value: editorData.minutes || '',
				/* translators: %d: estimated minutes. */
				help: sprintf( __( 'Leave empty to use the estimate: %d min.', 'mental-load-meter' ), estimate ),
				onChange: function ( value ) {
					setMeta( '_mlm_minutes', parseInt( value, 10 ) || 0 );
				}
			} )
		);
	}

	wp.plugins.registerPlugin( 'mental-load-meter', {
		render: MentalLoadPanel
	} );
} )( window.wp, window.mlmData || { levels: [], postTypes: [], wpm: 200 } );
