const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const crypto = require( 'crypto' );

// ---- Classic scripts --------------------------------------------------------
// editor sidebar + analytics chart
const scriptConfig = {
	...defaultConfig,
	entry: {
		index:     './src/index.js',
		analytics: './src/analytics.js',
	},
	output: {
		...defaultConfig.output,
		clean: false, // both configs share build/; let webpack overwrite in place.
	},
};

// ---- Script Module ----------------------------------------------------------
// view.js must be a true ES module with a STATIC `import` declaration for
// @wordpress/interactivity, so that our store() call is synchronous and runs
// before the Interactivity API's DOMContentLoaded handler fires.
//
// Using externalsType:'module' with a native `externals` map produces:
//   import * as X from '@wordpress/interactivity';   ← static, no async wrapper
// whereas ExternalsPlugin type:'import' produces a dynamic import() which
// wraps the module in webpack's async module system, causing a race condition
// where init() runs before store('wp-pop') is called.
const moduleConfig = {
	...defaultConfig,
	entry: {
		view: './src/view.js',
	},
	experiments: {
		...defaultConfig.experiments,
		outputModule: true,
	},
	output: {
		...defaultConfig.output,
		clean:       false,
		module:      true,
		chunkFormat: 'module',
		environment: {
			...defaultConfig.output?.environment,
			module: true,
		},
		library: {
			type: 'module',
		},
	},
	// Static external: webpack emits `import * as X from '@wordpress/interactivity'`
	// at the top of the ES module (no async wrapper, no top-level await).
	externalsType: 'module',
	externals: {
		'@wordpress/interactivity': '@wordpress/interactivity',
	},
	plugins: [
		// Strip DependencyExtractionWebpackPlugin (we handle externals above)
		// and add a lightweight plugin that writes view.asset.php.
		...defaultConfig.plugins.filter(
			( p ) => ! ( p instanceof DependencyExtractionWebpackPlugin )
		),
		{
			apply( compiler ) {
				compiler.hooks.emit.tap( 'ViewAssetPlugin', ( compilation ) => {
					const viewAsset = compilation.assets[ 'view.js' ];
					if ( ! viewAsset ) return;

					const hash = crypto
						.createHash( 'md5' )
						.update( viewAsset.source() )
						.digest( 'hex' )
						.slice( 0, 20 );

					const content =
						`<?php return array('dependencies' => array('@wordpress/interactivity'), 'version' => '${ hash }', 'type' => 'module');\n`;

					compilation.assets[ 'view.asset.php' ] = {
						source: () => content,
						size:   () => Buffer.byteLength( content ),
					};
				} );
			},
		},
	],
};

module.exports = [ scriptConfig, moduleConfig ];

