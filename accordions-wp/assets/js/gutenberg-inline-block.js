( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InnerBlocks = blockEditor.InnerBlocks;
    var RichText = blockEditor.RichText;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelColorSettings = blockEditor.PanelColorSettings;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var TextControl = components.TextControl;
    var RangeControl = components.RangeControl;

    var data = window.tcaccInlineData || {};
    var proBehavior = !! data.proBehavior;

    var THEMES = [
        { label: 'Sun Flower', value: 'theme1' },
        { label: 'Orange', value: 'theme2' },
        { label: 'Pumpkin', value: 'theme3' },
        { label: 'Alizarin', value: 'theme4' },
        { label: 'Carrot', value: 'theme5' },
        { label: 'Flat', value: 'theme-flat' },
        { label: 'Shadow', value: 'theme-shadow' },
        { label: 'Dark', value: 'theme-dark' }
    ];

    // Layouts come from the server: the available ones plus disabled "Pro" previews.
    var LAYOUTS = ( data.layouts && data.layouts.length ) ? data.layouts : [ { label: 'Accordion (stacked)', value: 'accordion' } ];

    var ICON_STYLES = [
        { label: 'Chevron', value: 'chevron' },
        { label: 'Plus / Minus', value: 'plus_minus' },
        { label: 'Angle', value: 'angle' },
        { label: 'Caret', value: 'caret' },
        { label: 'Folder', value: 'folder' },
        { label: 'None (no icon)', value: 'none' }
    ];

    var BORDER_STYLES = [
        { label: 'None', value: 'none' },
        { label: 'Solid', value: 'solid' },
        { label: 'Dashed', value: 'dashed' },
        { label: 'Dotted', value: 'dotted' },
        { label: 'Double', value: 'double' }
    ];

    // ---- Markup saved by earlier versions (kept so existing blocks are not flagged as invalid) ----
    var oldWrapperSave = function() {
        return el( 'ul', { className: 'responsive-accordion responsive-accordion-default' },
            el( InnerBlocks.Content )
        );
    };

    var previousWrapperSave = function( props ) {
        var attributes = props.attributes;

        return el( 'div', {
                className: 'tc-accordion-wrapper container ' + attributes.theme,
                'data-auto-open': String( attributes.autoOpen ),
                'data-closeable': 'yes',
                'data-close-others': attributes.closeOthers ? 'yes' : 'no',
                'data-open-event': 'click',
                'data-anim-speed': '300',
                'data-closed-icon': 'fa-plus',
                'data-open-icon': 'fa-minus',
                'data-layout': 'accordion',
                'data-deep-link': 'no'
            },
            el( 'ul', { className: 'responsive-accordion responsive-accordion-default' },
                el( InnerBlocks.Content )
            )
        );
    };

    var oldItemSave = function( props ) {
        return el( 'li', {},
            el( 'div', { className: 'responsive-accordion-head' },
                el( 'span', { className: 'responsive-accordion-title' }, props.attributes.title )
            ),
            el( 'div', { className: 'responsive-accordion-panel' },
                el( InnerBlocks.Content )
            )
        );
    };

    // 1. Parent Wrapper Block (rendered on the server with the same engine as saved accordions)
    blocks.registerBlockType( 'tcaccordion/inline-wrapper', {
        title: 'TCP Accordion (Inline)',
        icon: 'menu',
        category: 'design',
        keywords: [ 'accordion', 'inline', 'faq', 'toggle' ],
        attributes: {
            options: { type: 'object', default: {} }
        },
        edit: function( props ) {
            var options = props.attributes.options || {};

            // Read and write one setting (keys are the same ones the accordion editor saves).
            var get = function( key, fallback ) {
                return ( options[ key ] === undefined || options[ key ] === '' ) ? fallback : options[ key ];
            };
            var set = function( key ) {
                return function( value ) {
                    var next = Object.assign( {}, props.attributes.options || {} );
                    if ( value === undefined || value === null || value === '' ) {
                        delete next[ key ];
                    } else {
                        next[ key ] = String( value );
                    }
                    props.setAttributes( { options: next } );
                };
            };

            var select = function( label, key, choices, fallback, help ) {
                return el( SelectControl, { label: label, value: get( key, fallback ), options: choices, help: help, onChange: set( key ) } );
            };
            var toggle = function( label, key, fallbackYes, help ) {
                return el( ToggleControl, {
                    label: label,
                    help: help,
                    checked: get( key, fallbackYes ? 'yes' : 'no' ) === 'yes',
                    onChange: function( on ) { set( key )( on ? 'yes' : 'no' ); }
                } );
            };
            var range = function( label, key, min, max, step, fallback ) {
                return el( RangeControl, {
                    label: label,
                    value: parseFloat( get( key, fallback ) ),
                    min: min,
                    max: max,
                    step: step,
                    allowReset: true,
                    resetFallbackValue: fallback,
                    initialPosition: fallback,
                    onChange: function( value ) { set( key )( value === undefined ? '' : value ); }
                } );
            };

            // Colors: the block editor color panel, or plain hex fields on older editors.
            var colors = function( title, list, initialOpen ) {
                if ( PanelColorSettings ) {
                    return el( PanelColorSettings, {
                        title: title,
                        initialOpen: !! initialOpen,
                        colorSettings: list.map( function( item ) {
                            return {
                                label: item[ 0 ],
                                value: get( item[ 1 ], '' ),
                                onChange: function( color ) { set( item[ 1 ] )( color || '' ); }
                            };
                        } )
                    } );
                }
                return el( PanelBody, { title: title, initialOpen: !! initialOpen },
                    list.map( function( item ) {
                        return el( TextControl, { key: item[ 1 ], label: item[ 0 ] + ' (hex, e.g. #1d4ed8)', value: get( item[ 1 ], '' ), onChange: set( item[ 1 ] ) } );
                    } )
                );
            };

            var layout = get( '_tcacc_layout', 'accordion' );

            var sidebar = InspectorControls ? el(
                InspectorControls,
                null,
                el( PanelBody, { title: 'Layout & behavior', initialOpen: true },
                    select( 'Theme', 'custom_accordion_columns_post_themes', THEMES, 'theme1' ),
                    select( 'Layout', '_tcacc_layout', LAYOUTS, 'accordion', 'Tabs, Horizontal and Two-column apply on screens 768px and wider.' ),
                    ( layout === 'horizontal' && proBehavior ) ? range( 'Horizontal height (px)', '_tcacc_horizontal_height', 200, 900, 10, 380 ) : null,
                    el( TextControl, {
                        label: 'Open item number',
                        type: 'number',
                        min: 0,
                        value: get( '_tcacc_auto_open_item', '' ),
                        help: '0 keeps every item closed. Empty uses 1.',
                        onChange: set( '_tcacc_auto_open_item' )
                    } ),
                    toggle( 'Close other items when one opens', '_tcacc_close_others', true ),
                    toggle( 'Allow closing the open item', '_tcacc_is_closeable', true ),
                    proBehavior ? select( 'Open event', '_tcacc_open_event', [ { label: 'On click', value: 'click' }, { label: 'On mouse hover', value: 'hover' } ], 'click' ) : null,
                    proBehavior ? range( 'Animation speed (ms)', '_tcacc_anim_speed', 0, 1500, 50, 300 ) : el( 'p', { style: { color: '#64748b', margin: 0 } }, 'Hover open and animation speed are available in Pro.' )
                ),
                el( PanelBody, { title: 'Search, tools & SEO', initialOpen: false },
                    toggle( 'Search box', '_tcacc_show_search', false ),
                    get( '_tcacc_show_search', 'no' ) === 'yes' ? el( TextControl, { label: 'Search placeholder', value: get( '_tcacc_search_placeholder', '' ), placeholder: 'Search questions...', onChange: set( '_tcacc_search_placeholder' ) } ) : null,
                    toggle( 'Expand all / Collapse all buttons', '_tcacc_show_toggle_all', false ),
                    toggle( 'Deep links (shareable item URLs)', '_tcacc_deep_link', false ),
                    toggle( 'FAQ schema (JSON-LD)', '_tcacc_enable_faq_schema', false )
                ),
                el( PanelBody, { title: 'Icons', initialOpen: false },
                    select( 'Icon style', '_tcacc_icon_style', ICON_STYLES, 'chevron' ),
                    select( 'Show icons', '_tpaccpro_wiki_acc_themes_show_hide_icons', [ { label: 'Show', value: '1' }, { label: 'Hide', value: '2' } ], '1' ),
                    select( 'Icon position', '_tpaccpro_wiki_acc_themes_icon_position', [ { label: 'Left', value: '1' }, { label: 'Right', value: '2' } ], '2' )
                ),
                colors( 'Icon colors', [
                    [ 'Icon color', 'custom_accordion_icon_color' ],
                    [ 'Icon background', 'custom_accordion_icon_bg_color' ],
                    [ 'Icon color on hover', 'custom_accordion_icon_color_hover' ],
                    [ 'Icon color when open', 'custom_accordion_icon_color_active' ]
                ] ),
                el( PanelBody, { title: 'Title typography', initialOpen: false },
                    range( 'Font size (px)', 'custom_accordion_title_font_size', 10, 48, 1, 18 ),
                    range( 'Line height', 'custom_accordion_title_line_height', 1, 2.5, 0.1, 1.4 ),
                    select( 'Alignment', '_tpaccpro_wiki_acc_themes_title_position', [ { label: 'Left', value: 'left' }, { label: 'Center', value: 'center' }, { label: 'Right', value: 'right' } ], 'left' )
                ),
                colors( 'Title colors', [
                    [ 'Background', 'custom_accordion_title_bg_color' ],
                    [ 'Text', 'custom_accordion_title_font_color' ]
                ] ),
                colors( 'Title hover colors', [
                    [ 'Hover background', 'custom_accordion_title_bg_color_hover' ],
                    [ 'Hover text', 'custom_accordion_title_font_color_hover' ]
                ] ),
                colors( 'Title open (active) colors', [
                    [ 'Open background', 'custom_accordion_title_bg_color_active' ],
                    [ 'Open text', 'custom_accordion_title_font_color_active' ]
                ] ),
                el( PanelBody, { title: 'Content typography & spacing', initialOpen: false },
                    range( 'Font size (px)', 'custom_accordion_content_font_size', 10, 36, 1, 16 ),
                    range( 'Line height', 'custom_accordion_content_line_height', 1, 2.5, 0.1, 1.6 ),
                    range( 'Letter spacing (px)', 'custom_accordion_content_letter_spacing', 0, 5, 0.1, 0 ),
                    range( 'Content padding (px)', 'custom_accordion_content_padding', 0, 60, 1, 15 ),
                    range( 'Gap between items (px)', '_tpaccpro_wiki_acc_theme_content_margin', 0, 40, 1, 5 )
                ),
                colors( 'Content colors', [
                    [ 'Background', 'custom_accordion_content_bg_color' ],
                    [ 'Text', 'custom_accordion_content_font_color' ]
                ] ),
                el( PanelBody, { title: 'Border', initialOpen: false },
                    select( 'Border style', 'custom_accordion_border_style', BORDER_STYLES, 'none' ),
                    range( 'Border width (px)', 'custom_accordion_border_width', 0, 10, 1, 1 )
                ),
                colors( 'Border color', [
                    [ 'Border', 'custom_accordion_border_color' ]
                ] )
            ) : null;

            return el(
                Fragment,
                null,
                sidebar,
                el( 'div', { className: 'tc-accordion-inline-parent-editor', style: { padding: '15px', border: '2px dashed #cbd5e1', borderRadius: '8px' } },
                    el( 'p', { style: { margin: '0 0 4px 0', fontWeight: 'bold', color: '#64748b' } }, 'TCP Accordion Container' ),
                    el( 'p', { style: { margin: '0 0 10px 0', color: '#64748b', fontSize: '12px' } }, 'Select this container block to see all accordion settings in the sidebar. They apply on the live page.' ),
                    el( InnerBlocks, {
                        allowedBlocks: [ 'tcaccordion/inline-item' ],
                        template: [
                            [ 'tcaccordion/inline-item' ],
                            [ 'tcaccordion/inline-item' ]
                        ]
                    } )
                )
            );
        },
        // Dynamic block: only the inner items are stored, the front end is built by the plugin.
        save: function() {
            return el( InnerBlocks.Content );
        },
        deprecated: [
            {
                attributes: {
                    theme: { type: 'string', default: 'theme1' },
                    autoOpen: { type: 'number', default: 1 },
                    closeOthers: { type: 'boolean', default: true }
                },
                save: previousWrapperSave,
                migrate: function( attributes ) {
                    var options = {};
                    if ( attributes.theme ) {
                        options.custom_accordion_columns_post_themes = attributes.theme;
                    }
                    if ( typeof attributes.autoOpen === 'number' ) {
                        options._tcacc_auto_open_item = String( attributes.autoOpen );
                    }
                    options._tcacc_close_others = attributes.closeOthers === false ? 'no' : 'yes';
                    return { options: options };
                }
            },
            {
                attributes: {},
                save: oldWrapperSave
            }
        ]
    } );

    // 2. Child Single Accordion Item Block
    blocks.registerBlockType( 'tcaccordion/inline-item', {
        title: 'Accordion Item',
        icon: 'minus',
        parent: [ 'tcaccordion/inline-wrapper' ],
        attributes: {
            title: {
                type: 'string',
                default: ''
            }
        },
        edit: function( props ) {
            return el( 'div', { className: 'tc-accordion-inline-item-editor', style: { marginBottom: '10px', padding: '10px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '6px' } },
                el( RichText, {
                    tagName: 'div',
                    className: 'tc-item-title-field',
                    style: { fontWeight: '600', padding: '8px', background: '#ffffff', border: '1px solid #cbd5e1', borderRadius: '4px', marginBottom: '8px' },
                    placeholder: 'Enter Accordion Question / Title...',
                    value: props.attributes.title,
                    onChange: function( newTitle ) {
                        props.setAttributes( { title: newTitle } );
                    }
                } ),
                el( InnerBlocks, {
                    template: [ [ 'core/paragraph', { placeholder: 'Enter accordion panel content...' } ] ]
                } )
            );
        },
        save: function( props ) {
            return el( 'li', {},
                el( 'div', { className: 'responsive-accordion-head', role: 'button', tabIndex: 0, 'aria-expanded': 'false' },
                    el( RichText.Content, { tagName: 'span', className: 'responsive-accordion-title', value: props.attributes.title } ),
                    el( 'span', { className: 'responsive-accordion-icon', 'aria-hidden': 'true' },
                        el( 'i', { className: 'fa-solid fa-plus fa-fw' } )
                    )
                ),
                el( 'div', { className: 'responsive-accordion-panel', style: { display: 'none' } },
                    el( InnerBlocks.Content )
                )
            );
        },
        deprecated: [
            {
                attributes: {
                    title: { type: 'string', default: '' }
                },
                save: oldItemSave
            }
        ]
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor || window.wp.editor,
    window.wp.components
);
