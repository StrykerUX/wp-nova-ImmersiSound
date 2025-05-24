/**
 * Nova Sound FX Gutenberg Blocks
 */
(function(blocks, element, components, editor, i18n) {
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { InspectorControls, BlockControls } = editor;
    const { 
        PanelBody, 
        SelectControl, 
        ToggleControl, 
        RangeControl,
        TextControl,
        Button,
        ToolbarGroup,
        ToolbarButton
    } = components;
    const { __ } = i18n;

    // Icono del plugin
    const novaSoundIcon = el('svg', { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
        el('path', {
            d: 'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z',
            fill: 'currentColor'
        })
    );

    /**
     * Bloque de Controles de Sonido
     */
    registerBlockType('nova-sound-fx/controls', {
        title: __('Nova Sound FX Controls', 'nova-sound-fx'),
        description: __('Add sound controls widget to your page', 'nova-sound-fx'),
        icon: novaSoundIcon,
        category: 'widgets',
        attributes: {
            style: {
                type: 'string',
                default: 'minimal'
            },
            position: {
                type: 'string',
                default: 'bottom-right'
            },
            theme: {
                type: 'string',
                default: 'light'
            },
            showVolume: {
                type: 'boolean',
                default: true
            },
            showSave: {
                type: 'boolean',
                default: true
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { style, position, theme, showVolume, showSave } = attributes;

            return [
                el(InspectorControls, {},
                    el(PanelBody, { 
                        title: __('Control Settings', 'nova-sound-fx'),
                        initialOpen: true 
                    },
                        el(SelectControl, {
                            label: __('Style', 'nova-sound-fx'),
                            value: style,
                            options: [
                                { label: __('Minimal', 'nova-sound-fx'), value: 'minimal' },
                                { label: __('Floating', 'nova-sound-fx'), value: 'floating' },
                                { label: __('Embedded', 'nova-sound-fx'), value: 'embedded' }
                            ],
                            onChange: (value) => setAttributes({ style: value })
                        }),
                        el(SelectControl, {
                            label: __('Position', 'nova-sound-fx'),
                            value: position,
                            options: [
                                { label: __('Top Left', 'nova-sound-fx'), value: 'top-left' },
                                { label: __('Top Right', 'nova-sound-fx'), value: 'top-right' },
                                { label: __('Bottom Left', 'nova-sound-fx'), value: 'bottom-left' },
                                { label: __('Bottom Right', 'nova-sound-fx'), value: 'bottom-right' }
                            ],
                            onChange: (value) => setAttributes({ position: value }),
                            help: style === 'embedded' ? __('Position is ignored for embedded style', 'nova-sound-fx') : ''
                        }),
                        el(SelectControl, {
                            label: __('Theme', 'nova-sound-fx'),
                            value: theme,
                            options: [
                                { label: __('Light', 'nova-sound-fx'), value: 'light' },
                                { label: __('Dark', 'nova-sound-fx'), value: 'dark' }
                            ],
                            onChange: (value) => setAttributes({ theme: value })
                        }),
                        el(ToggleControl, {
                            label: __('Show Volume Slider', 'nova-sound-fx'),
                            checked: showVolume,
                            onChange: (value) => setAttributes({ showVolume: value })
                        }),
                        el(ToggleControl, {
                            label: __('Show Save Button', 'nova-sound-fx'),
                            checked: showSave,
                            onChange: (value) => setAttributes({ showSave: value })
                        })
                    )
                ),
                el('div', { className: 'nova-sound-fx-controls-preview' },
                    el('div', { 
                        className: `nova-preview-badge nova-preview-${style}` 
                    },
                        el('span', { className: 'dashicons dashicons-format-audio' }),
                        el('span', {}, __('Nova Sound FX Controls', 'nova-sound-fx'))
                    ),
                    el('p', { className: 'nova-preview-info' }, 
                        __('The sound controls will appear here in the frontend.', 'nova-sound-fx')
                    )
                )
            ];
        },

        save: function() {
            // El renderizado se hace en PHP
            return null;
        }
    });

    /**
     * Bloque de Botón con Sonido
     */
    registerBlockType('nova-sound-fx/sound-button', {
        title: __('Sound Button', 'nova-sound-fx'),
        description: __('A button that plays a sound effect', 'nova-sound-fx'),
        icon: novaSoundIcon,
        category: 'widgets',
        attributes: {
            text: {
                type: 'string',
                default: 'Click me!'
            },
            soundId: {
                type: 'number',
                default: 0
            },
            eventType: {
                type: 'string',
                default: 'click'
            },
            volume: {
                type: 'number',
                default: 100
            },
            className: {
                type: 'string',
                default: ''
            },
            buttonStyle: {
                type: 'string',
                default: 'primary'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { text, soundId, eventType, volume, className, buttonStyle } = attributes;

            // Función para reproducir sonido de vista previa
            const playPreviewSound = () => {
                const sound = novaSoundFXBlocks.sounds.find(s => s.value === soundId);
                if (sound && sound.url) {
                    const audio = new Audio(sound.url);
                    audio.volume = volume / 100;
                    audio.play().catch(e => console.error('Preview play failed:', e));
                }
            };

            return [
                el(InspectorControls, {},
                    el(PanelBody, { 
                        title: __('Button Settings', 'nova-sound-fx'),
                        initialOpen: true 
                    },
                        el(TextControl, {
                            label: __('Button Text', 'nova-sound-fx'),
                            value: text,
                            onChange: (value) => setAttributes({ text: value })
                        }),
                        el(SelectControl, {
                            label: __('Button Style', 'nova-sound-fx'),
                            value: buttonStyle,
                            options: [
                                { label: __('Primary', 'nova-sound-fx'), value: 'primary' },
                                { label: __('Secondary', 'nova-sound-fx'), value: 'secondary' },
                                { label: __('Outline', 'nova-sound-fx'), value: 'outline' },
                                { label: __('Text', 'nova-sound-fx'), value: 'text' }
                            ],
                            onChange: (value) => setAttributes({ buttonStyle: value })
                        }),
                        el(TextControl, {
                            label: __('Additional CSS Class', 'nova-sound-fx'),
                            value: className,
                            onChange: (value) => setAttributes({ className: value })
                        })
                    ),
                    el(PanelBody, { 
                        title: __('Sound Settings', 'nova-sound-fx'),
                        initialOpen: true 
                    },
                        el(SelectControl, {
                            label: __('Sound Effect', 'nova-sound-fx'),
                            value: soundId,
                            options: [
                                { label: __('— Select Sound —', 'nova-sound-fx'), value: 0 },
                                ...novaSoundFXBlocks.sounds
                            ],
                            onChange: (value) => setAttributes({ soundId: parseInt(value) })
                        }),
                        soundId > 0 && el(Button, {
                            isSecondary: true,
                            onClick: playPreviewSound,
                            style: { marginBottom: '10px' }
                        }, __('Preview Sound', 'nova-sound-fx')),
                        el(SelectControl, {
                            label: __('Trigger Event', 'nova-sound-fx'),
                            value: eventType,
                            options: Object.entries(novaSoundFXBlocks.eventTypes).map(([value, label]) => ({
                                value,
                                label
                            })),
                            onChange: (value) => setAttributes({ eventType: value })
                        }),
                        el(RangeControl, {
                            label: __('Volume', 'nova-sound-fx'),
                            value: volume,
                            onChange: (value) => setAttributes({ volume: value }),
                            min: 0,
                            max: 100,
                            help: __('Adjust the volume for this sound effect', 'nova-sound-fx')
                        })
                    )
                ),
                el(BlockControls, {},
                    el(ToolbarGroup, {},
                        el(ToolbarButton, {
                            icon: 'controls-play',
                            title: __('Preview Sound', 'nova-sound-fx'),
                            onClick: playPreviewSound,
                            isDisabled: soundId === 0
                        })
                    )
                ),
                el('div', { 
                    className: 'wp-block-button nova-sound-button-preview' 
                },
                    el('button', {
                        className: `wp-block-button__link is-style-${buttonStyle} ${className}`,
                        type: 'button'
                    }, 
                        text,
                        soundId > 0 && el('span', { 
                            className: 'nova-sound-indicator',
                            style: { marginLeft: '8px' }
                        }, '🔊')
                    )
                )
            ];
        },

        save: function() {
            // El renderizado se hace en PHP
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor || window.wp.editor,
    window.wp.i18n
);
