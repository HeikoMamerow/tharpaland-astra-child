#!/usr/bin/env node

const bs = require( 'browser-sync' ).create();

bs.init( {
	proxy: {
		target: 'https://tharpaland.local',
	},
	url: 'https://localhost:3003',
	https: true,
	files: [ './**/*' ],
	notify: false,
	open: false,
	reloadOnRestart: true,
} );
