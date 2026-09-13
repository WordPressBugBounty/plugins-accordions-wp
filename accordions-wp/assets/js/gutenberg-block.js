( function( blocks, element, components, serverSideRender ) {
    var el = element.createElement;
    // Fallback support for older vs modern WP ServerSideRender component locations
    var ServerSideRender = serverSideRender || components.ServerSideRender || window.wp.serverSideRender;

    blocks.registerBlockType( 'tcaccordion/select-accordion', {
        title: ( window.tcaccGutenbergData && window.tcaccGutenbergData.title ) ? window.tcaccGutenbergData.title : 'TCP Accordion',
        icon: 'list-view',
        category: 'widgets',
        keywords: [ 'accordion', 'tcp', 'faq', 'toggle' ],
        attributes: {
            accordionId: {
                type: 'string',
                default: ''
            }
        },
        edit: function( props ) {
            var options = [ { label: '-- Select an Accordion --', value: '' } ];

            if ( window.tcaccGutenbergData && window.tcaccGutenbergData.accordions ) {
                options = options.concat( window.tcaccGutenbergData.accordions );
            }

            var selectedId = props.attributes.accordionId;

            return el(
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
                // 1. Dropdown Picker Control
                el( components.SelectControl, {
                    label: 'Select Accordion to Display:',
                    value: selectedId,
                    options: options,
                    onChange: function( newId ) {
                        props.setAttributes( { accordionId: newId } );
                    }
                } ),

                // 2. Dynamic Server-Side Live Preview
                selectedId ? el( ServerSideRender, {
                    block: 'tcaccordion/select-accordion',
                    attributes: props.attributes
                } ) : el(
                    'p',
                    { style: { margin: '10px 0 0 0', color: '#64748b', fontSize: '13px' } },
                    'Please select an accordion from the dropdown above to view the preview.'
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
    window.wp.serverSideRender
);