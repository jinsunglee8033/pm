/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';

    config.extraPlugins = 'widget';
    config.extraPlugins = 'uploadwidget';
    config.extraPlugins = 'uploadimage';
    config.extraPlugins = 'lineutils';
    config.extraPlugins = 'clipboard';
    config.extraPlugins = 'dialog';
    config.extraPlugins = 'dialogui';
    config.extraPlugins = 'widgetselection';
    config.extraPlugins = 'filetools';
    config.extraPlugins = 'notificationaggregator';
    config.extraPlugins = 'notification';
    config.extraPlugins = 'toolbar';
    config.extraPlugins = 'button';
    config.extraPlugins = 'image2';
    config.extraPlugins = 'colorbutton';
    config.extraPlugins = 'panel';
    config.extraPlugins = 'panelbutton';
    config.extraPlugins = 'floatpanel';
    config.extraPlugins = 'lineheight';
    config.extraPlugins = 'richcombo';
    config.extraPlugins = 'listblock';

    //config.uploadUrl = '/ckeditor/upload';
    config.imageUploadUrl = '/ckeditor/upload';

    config.line_height="0.5px;0.7.5px;1px;1.1px;1.2px;1.3px;1.4px;1.5px" ;



    //config.filebrowserUploadUrl = '/ckeditor/upload';
    //config.filebrowserUploadUrl = '/ckeditor/upload';
};
