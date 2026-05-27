/**
 * WP Pop! Analytics Chart.
 *
 * Renders a Chart.js line chart using data localized via wpPopAnalytics.
 *
 * @since   0.2.0
 * @package Wp_Pop
 */

import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip } from 'chart.js';

Chart.register( LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip );

document.addEventListener( 'DOMContentLoaded', () => {
	const canvas = document.getElementById( 'wp-pop-chart' );
	if ( ! canvas ) return;

	const {
		labels     = [],
		views      = [],
		clicks     = [],
		dismissals = [],
		labelViews   = 'Views',
		labelClicks  = 'Clicks',
		labelDismiss = 'Dismissals',
	} = window.wpPopAnalytics || {};

	new Chart( canvas, {
		type: 'line',
		data: {
			labels,
			datasets: [
				{
					label: labelViews,
					data: views,
					borderColor: '#0073aa',
					backgroundColor: 'rgba(0,115,170,0.1)',
					fill: true,
					tension: 0.3,
				},
				{
					label: labelClicks,
					data: clicks,
					borderColor: '#46b450',
					backgroundColor: 'rgba(70,180,80,0.1)',
					fill: true,
					tension: 0.3,
				},
				{
					label: labelDismiss,
					data: dismissals,
					borderColor: '#dc3232',
					backgroundColor: 'rgba(220,50,50,0.1)',
					fill: true,
					tension: 0.3,
				},
			],
		},
		options: {
			responsive: true,
			interaction: {
				mode: 'index',
				intersect: false,
			},
			scales: {
				x: {
					grid: { display: false },
				},
				y: {
					beginAtZero: true,
					ticks: { precision: 0 },
				},
			},
			plugins: {
				legend: { position: 'top' },
				tooltip: { mode: 'index' },
			},
		},
	} );
} );
