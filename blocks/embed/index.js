/**
 * Editor UI for the forumulate/embed block.
 *
 * Plain ES5 against the wp.* globals (see index.asset.php), so there is no
 * build step and the shipped file is the source. The block is dynamic: it
 * saves nothing but its attributes and renders on the server.
 *
 * The script embeds (comments, widget, changelog) run on the published page,
 * not inside the editor, so the editor shows a summary of what will appear.
 * A feed is plain HTML, so the editor previews the real thing.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var blockEditor = wp.blockEditor;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;

	var KINDS = [
		{ value: 'comments', label: __( 'Comments', 'forumulate' ) },
		{ value: 'widget', label: __( 'Docs search or ask', 'forumulate' ) },
		{ value: 'changelog', label: __( 'Changelog bell', 'forumulate' ) },
		{ value: 'feed', label: __( 'Feed (blog, changelog or docs)', 'forumulate' ) },
	];

	function summary( a ) {
		var space = a.space || __( 'the space from Settings → Forumulate', 'forumulate' );
		if ( a.kind === 'comments' ) {
			return a.topic
				? __( 'Shows the comment thread for one topic, from ', 'forumulate' ) + space + '.'
				: __( 'Shows the comment thread for this page, from ', 'forumulate' ) + space + '. ' +
						__( 'The first visit creates the thread.', 'forumulate' );
		}
		if ( a.kind === 'widget' ) {
			return ( a.mode === 'ask'
				? __( 'An "ask a question" box answered from ', 'forumulate' )
				: __( 'A search box over ', 'forumulate' ) ) + space + '.';
		}
		return __( 'A "What\'s new" bell with an unread badge, from ', 'forumulate' ) + space + '.';
	}

	function controls( a, set ) {
		var text = components.TextControl;
		var select = components.SelectControl;
		var fields = [
			el( select, {
				key: 'kind',
				label: __( 'Show', 'forumulate' ),
				value: a.kind,
				options: KINDS,
				onChange: function ( v ) { set( { kind: v } ); },
			} ),
			el( text, {
				key: 'space',
				label: __( 'Space id or slug', 'forumulate' ),
				help: __( 'Leave empty to use the space from Settings → Forumulate.', 'forumulate' ),
				value: a.space,
				onChange: function ( v ) { set( { space: v } ); },
			} ),
		];
		if ( a.kind === 'comments' ) {
			fields.push(
				el( text, {
					key: 'topic',
					label: __( 'Topic id (optional)', 'forumulate' ),
					help: __( 'Empty: one thread per page, found by the page address.', 'forumulate' ),
					value: a.topic,
					onChange: function ( v ) { set( { topic: v } ); },
				} ),
				el( select, {
					key: 'theme',
					label: __( 'Theme', 'forumulate' ),
					value: a.theme,
					options: [
						{ value: '', label: __( 'From settings', 'forumulate' ) },
						{ value: 'light', label: __( 'Light', 'forumulate' ) },
						{ value: 'dark', label: __( 'Dark', 'forumulate' ) },
					],
					onChange: function ( v ) { set( { theme: v } ); },
				} )
			);
		}
		if ( a.kind === 'widget' ) {
			fields.push(
				el( select, {
					key: 'mode',
					label: __( 'Mode', 'forumulate' ),
					value: a.mode,
					options: [
						{ value: 'search', label: __( 'Search', 'forumulate' ) },
						{ value: 'ask', label: __( 'Ask (AI answer)', 'forumulate' ) },
					],
					onChange: function ( v ) { set( { mode: v } ); },
				} ),
				el( text, {
					key: 'placeholder',
					label: __( 'Placeholder', 'forumulate' ),
					value: a.placeholder,
					onChange: function ( v ) { set( { placeholder: v } ); },
				} )
			);
		}
		if ( a.kind === 'feed' ) {
			fields.push(
				el( select, {
					key: 'feedType',
					label: __( 'Feed', 'forumulate' ),
					value: a.feedType,
					options: [
						{ value: 'blog', label: __( 'Blog posts', 'forumulate' ) },
						{ value: 'changelog', label: __( 'Changelog releases', 'forumulate' ) },
						{ value: 'docs', label: __( 'Recently updated docs', 'forumulate' ) },
					],
					onChange: function ( v ) { set( { feedType: v } ); },
				} ),
				el( text, {
					key: 'title',
					label: __( 'Heading (optional)', 'forumulate' ),
					value: a.title,
					onChange: function ( v ) { set( { title: v } ); },
				} ),
				el( components.RangeControl, {
					key: 'limit',
					label: __( 'Items', 'forumulate' ),
					value: a.limit,
					min: 1,
					max: 20,
					onChange: function ( v ) { set( { limit: v || 5 } ); },
				} ),
				el( components.ToggleControl, {
					key: 'showExcerpt',
					label: __( 'Show dates and excerpts', 'forumulate' ),
					checked: !! a.showExcerpt,
					onChange: function ( v ) { set( { showExcerpt: v } ); },
				} )
			);
		}
		return el(
			blockEditor.InspectorControls,
			{ key: 'inspector' },
			el( components.PanelBody, { title: __( 'Forumulate', 'forumulate' ) }, fields )
		);
	}

	wp.blocks.registerBlockType( 'forumulate/embed', {
		edit: function ( props ) {
			var a = props.attributes;
			var body =
				a.kind === 'feed'
					? el( ServerSideRender, { block: 'forumulate/embed', attributes: a } )
					: el(
							components.Placeholder,
							{
								icon: 'format-chat',
								label: ( KINDS.filter( function ( k ) { return k.value === a.kind; } )[ 0 ] || KINDS[ 0 ] ).label,
								instructions: summary( a ),
							},
							el( 'p', { className: 'forumulate-editor-note' }, __( 'It appears on the published page.', 'forumulate' ) )
						);
			return el(
				'div',
				blockEditor.useBlockProps(),
				controls( a, props.setAttributes ),
				body
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
