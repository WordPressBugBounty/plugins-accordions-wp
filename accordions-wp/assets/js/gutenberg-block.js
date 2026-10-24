( function( blocks, element, components, blockEditor, serverSideRender ) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = ( blockEditor && blockEditor.InspectorControls ) || ( window.wp.editor && window.wp.editor.InspectorControls );
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;

    // Fallback support for older vs modern WP ServerSideRender component locations
    var ServerSideRender = serverSideRender || components.ServerSideRender || window.wp.serverSideRender;

    var data = window.tcaccGutenbergData || {};
    var adminUrl = data.adminUrl || '';

    // Layouts come from the server: the available ones plus disabled "Pro" previews.
    var layoutOptions = [ { label: 'Use the accordion setting', value: '' } ].concat(
        ( data.layouts && data.layouts.length ) ? data.layouts : [ { label: 'Accordion (stacked)', value: 'accordion' } ]
    );

    var showHideOptions = function( yesLabel, noLabel ) {
        return [
            { label: 'Use the accordion setting', value: '' },
            { label: yesLabel, value: 'yes' },
            { label: noLabel, value: 'no' }
        ];
    };

    blocks.registerBlockType( 'tcaccordion/select-accordion', {
        title: data.title ? data.title : 'TCP Accordion',
        icon: 'list-view',
        category: 'widgets',
        keywords: [ 'accordion', 'tcp', 'faq', 'toggle' ],
        attributes: {
            accordionId: { type: 'string', default: '' },
            layout: { type: 'string', default: '' },
            showSearch: { type: 'string', default: '' },
            expandAll: { type: 'string', default: '' },
            deepLink: { type: 'string', default: '' },
            autoOpen: { type: 'string', default: '' }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var selectedId = attributes.accordionId;
            var options = [ { label: '-- Select an Accordion --', value: '' } ];

            if ( data.accordions ) {
                options = options.concat( data.accordions );
            }

            var set = function( name ) {
                return function( value ) {
                    var change = {};
                    change[ name ] = value;
                    props.setAttributes( change );
                };
            };

            var editLink = selectedId ? el(
                'p',
                { style: { margin: '0 0 12px' } },
                el( 'a', { href: adminUrl + 'post.php?post=' + selectedId + '&action=edit', target: '_blank', rel: 'noopener noreferrer' }, 'Edit this accordion' )
            ) : null;

            var newLink = el(
                'p',
                { style: { margin: '0' } },
                el( 'a', { href: adminUrl + 'post-new.php?post_type=accordion_tp', target: '_blank', rel: 'noopener noreferrer' }, 'Create a new accordion' )
            );

            var sidebar = InspectorControls ? el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: 'Accordion', initialOpen: true },
                    el( SelectControl, {
                        label: 'Accordion to display',
                        value: selectedId,
                        options: options,
                        onChange: set( 'accordionId' )
                    } ),
                    editLink,
                    newLink
                ),
                el(
                    PanelBody,
                    { title: 'Display for this page only', initialOpen: false },
                    el( 'p', { style: { marginTop: 0, color: '#64748b' } }, 'Change how this accordion looks here without editing the accordion itself.' ),
                    el( SelectControl, { label: 'Layout', value: attributes.layout, options: layoutOptions, onChange: set( 'layout' ) } ),
                    el( SelectControl, { label: 'Search box', value: attributes.showSearch, options: showHideOptions( 'Show', 'Hide' ), onChange: set( 'showSearch' ) } ),
                    el( SelectControl, { label: 'Expand / Collapse all', value: attributes.expandAll, options: showHideOptions( 'Show', 'Hide' ), onChange: set( 'expandAll' ) } ),
                    el( SelectControl, { label: 'Deep links', value: attributes.deepLink, options: showHideOptions( 'Enabled', 'Disabled' ), onChange: set( 'deepLink' ) } ),
                    el( TextControl, {
                        label: 'Open item number',
                        type: 'number',
                        min: 0,
                        value: attributes.autoOpen,
                        help: 'Leave empty to use the accordion setting. 0 opens nothing.',
                        onChange: set( 'autoOpen' )
                    } )
                )
            ) : null;

            return el(
                Fragment,
                null,
                sidebar,
                el(
                    'div',
                    {
                        className: 'tcacc-gutenberg-editor-wrapper',
                        style: {
                            padding: '16px',
                            background: '#ffffff',
                            border: '1px solid #cbd5e1',
                            borderRadius: '8px',
                            boxShadow: '0 1px 3px rgba(0,0,0,0.05)'
                        }
                    },
                    // 1. Dropdown Picker Control (also available in the sidebar)
                    el( SelectControl, {
                        label: 'Select Accordion to Display:',
                        value: selectedId,
                        options: options,
                        onChange: set( 'accordionId' )
                    } ),

                    // 2. Dynamic Server-Side Preview (styling only, items are not interactive in the editor)
                    selectedId ? el( ServerSideRender, {
                        block: 'tcaccordion/select-accordion',
                        attributes: attributes
                    } ) : el(
                        'p',
                        { style: { margin: '10px 0 0 0', color: '#64748b', fontSize: '13px' } },
                        ( data.accordions && data.accordions.length )
                            ? 'Please select an accordion from the dropdown above to view the preview.'
                            : 'No published accordion found yet. Create one from Accordions > Add New.'
                    )
                )
            );
        },
        save: function() {
            // Server-rendered dynamic block
            return null;
        }
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor,
    window.wp.serverSideRender
);
