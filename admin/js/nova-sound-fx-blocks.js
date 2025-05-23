/**
 * Nova Sound FX Gutenberg Blocks
 */
(function (blocks, element, components, editor, i18n) {
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { Button, PanelBody, RangeControl, SelectControl, ToggleControl } = components;
    const { InspectorControls, BlockControls, AlignmentToolbar } = editor;
    const { __ } = i18n;

    // Sound Button Block
    registerBlockType('nova-sound-fx/sound-button', {
        title: __('Sound Button', 'nova-sound-fx'),
        description: __('A button that plays a sound when clicked', 'nova-sound-fx'),
        icon: 'controls-volumeon',
        category: 'common',
        attributes: {
            text: {
                type: 'string',
                default: 'Click me!'
            },
            soundId: {
                type: 'number',
                default: 0
            },
            volume: {
                type: 'number',
                default: 100
            },
            align: {
                type: 'string',
                default: 'none'
            }
        },

        edit: function (props) {
            const { attributes, setAttributes, className } = props;
            const { text, soundId, volume, align } = attributes;

            // Prepare sound options
            const soundOptions = [
                { value: 0, label: __('Select a sound', 'nova-sound-fx') }
            ];
            
            if (window.novaSoundFXBlocks && window.novaSoundFXBlocks.sounds) {
                soundOptions.push(...window.novaSoundFXBlocks.sounds);
            }

            // Find selected sound URL
            const selectedSound = soundOptions.find(sound => sound.value === soundId);
            const soundUrl = selectedSound ? selectedSound.url : '';

            // Play preview sound
            const playPreview = () => {
                if (soundUrl) {
                    const audio = new Audio(soundUrl);
                    audio.volume = volume / 100;
                    audio.play();
                }
            };

            return [
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Sound Settings', 'nova-sound-fx') },
                        el(SelectControl, {
                            label: __('Sound Effect', 'nova-sound-fx'),
                            value: soundId,
                            options: soundOptions,
                            onChange: (value) => setAttributes({ soundId: parseInt(value) })
                        }),
                        el(RangeControl, {
                            label: __('Volume', 'nova-sound-fx'),
                            value: volume,
                            onChange: (value) => setAttributes({ volume: value }),
                            min: 0,
                            max: 100
                        }),
                        soundId > 0 && el(Button, {
                            isPrimary: true,
                            onClick: playPreview
                        }, __('Preview Sound', 'nova-sound-fx'))
                    )
                ),
                el(BlockControls, {},
                    el(AlignmentToolbar, {
                        value: align,
                        onChange: (value) => setAttributes({ align: value })
                    })
                ),
                el('div', { className: className },
                    el('div', { className: 'wp-block-button' + (align ? ' align' + align : '') },
                        el('button', {
                            className: 'wp-block-button__link',
                            onClick: playPreview
                        }, text)
                    ),
                    el('input', {
                        type: 'text',
                        value: text,
                        onChange: (e) => setAttributes({ text: e.target.value }),
                        placeholder: __('Button text...', 'nova-sound-fx'),
                        style: { marginTop: '10px', width: '100%' }
                    })
                )
            ];
        },

        save: function () {
            // Rendered by PHP
            return null;
        }
    });

    // Sound Controls Block
    registerBlockType('nova-sound-fx/sound-controls', {
        title: __('Sound Controls', 'nova-sound-fx'),
        description: __('User controls for sound effects', 'nova-sound-fx'),
        icon: 'admin-settings',
        category: 'common',
        attributes: {
            style: {
                type: 'string',
                default: 'minimal'
            },
            position: {
                type: 'string',
                default: 'inline'
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

        edit: function (props) {
            const { attributes, setAttributes, className } = props;
            const { style, position, theme, showVolume, showSave } = attributes;

            const styleOptions = [
                { value: 'minimal', label: __('Minimal', 'nova-sound-fx') },
                { value: 'floating', label: __('Floating', 'nova-sound-fx') },
                { value: 'embedded', label: __('Embedded', 'nova-sound-fx') }
            ];

            const positionOptions = [
                { value: 'inline', label: __('Inline', 'nova-sound-fx') },
                { value: 'top-left', label: __('Top Left', 'nova-sound-fx') },
                { value: 'top-right', label: __('Top Right', 'nova-sound-fx') },
                { value: 'bottom-left', label: __('Bottom Left', 'nova-sound-fx') },
                { value: 'bottom-right', label: __('Bottom Right', 'nova-sound-fx') }
            ];

            const themeOptions = [
                { value: 'light', label: __('Light', 'nova-sound-fx') },
                { value: 'dark', label: __('Dark', 'nova-sound-fx') }
            ];

            return [
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Control Settings', 'nova-sound-fx') },
                        el(SelectControl, {
                            label: __('Style', 'nova-sound-fx'),
                            value: style,
                            options: styleOptions,
                            onChange: (value) => setAttributes({ style: value })
                        }),
                        el(SelectControl, {
                            label: __('Position', 'nova-sound-fx'),
                            value: position,
                            options: positionOptions,
                            onChange: (value) => setAttributes({ position: value })
                        }),
                        el(SelectControl, {
                            label: __('Theme', 'nova-sound-fx'),
                            value: theme,
                            options: themeOptions,
                            onChange: (value) => setAttributes({ theme: value })
                        }),
                        el(ToggleControl, {
                            label: __('Show Volume Control', 'nova-sound-fx'),
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
                el('div', { className: className },
                    el('div', { 
                        style: { 
                            padding: '20px', 
                            backgroundColor: '#f0f0f0', 
                            textAlign: 'center',
                            borderRadius: '5px'
                        } 
                    },
                        el('p', {}, __('Sound Controls Widget', 'nova-sound-fx')),
                        el('p', { style: { fontSize: '12px', marginTop: '5px' } }, 
                            __('Style: ', 'nova-sound-fx') + style + ' | ' +
                            __('Position: ', 'nova-sound-fx') + position + ' | ' +
                            __('Theme: ', 'nova-sound-fx') + theme
                        )
                    )
                )
            ];
        },

        save: function () {
            // Rendered by PHP
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.editor,
    window.wp.i18n
);
