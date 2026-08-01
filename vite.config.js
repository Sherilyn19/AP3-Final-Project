/*
 * vite.config.js 
 * Vite configuration file
 * 
 * Configures Vite build tool with Laravel and React plugins.
 * Sets up asset input paths and development server watch settings.
 * 
 * @type {import('vite').UserConfig}
 */

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/instructor.css',
                'resources/css/style.css',
                'resources/css/student-instructor.css',

                'resources/js/script.js',
                'resources/js/app.js',

                'resources/js/admin.jsx',

                'resources/js/admin-pages.js',
                'resources/js/admin-pages/instrument.js',
                'resources/js/admin-pages/specialization.js',
                'resources/js/admin-pages/genre.js',
                'resources/js/admin-pages/payment-method.js',
                'resources/js/admin-pages/payment-status.js',
                'resources/js/admin-pages/lesson-session.js',
                'resources/js/admin-pages/user.js',
                'resources/js/admin-pages/user-create.js',
                'resources/js/admin-pages/student.js',
                'resources/js/admin-pages/instructor.js',
                'resources/js/admin-pages/schedule.js',

                'resources/js/admin-pages/reports-chart.js',
                'resources/js/admin-pages/chart-utils.js',
                'resources/js/admin-pages/dashboard-charts.js',
                'resources/js/admin-pages/student-risk-dashboard-widget.js',
                'resources/js/admin-pages/student-risk-analytics.js',

                'resources/js/student-instructor.js',

                'resources/js/emerging-tech/pitch-monitor-controller.js',
                'resources/js/emerging-tech/pitch-monitor-processor.js',
                'resources/js/student/student-dashboard-stat-cards.js',
                'resources/js/student/student-dashboard-upcoming-filter.js',
                'resources/js/student/schedule-filter-cards.js',
                'resources/js/student/enrollment-filter.js',
            ],
            refresh: true,
        }),

        react({
            jsxRuntime: 'automatic',
            fastRefresh: false,
        }),
    ],

    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

