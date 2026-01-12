/**
 * Main Application JavaScript
 */

(function() {
    'use strict';

    // Global app object
    window.App = {
        config: {
            apiUrl: '/api',
            ajaxUrl: '/ajax'
        },

        // Show notification
        notify: function(message, type = 'info') {
            Swal.fire({
                text: message,
                icon: type,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },

        // Show confirmation dialog
        confirm: function(message, title = 'Are you sure?') {
            return Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#f44336',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            });
        },

        // Format number
        formatNumber: function(number, decimals = 0) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        },

        // Format date
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        },

        // Format duration
        formatDuration: function(seconds) {
            if (seconds < 60) {
                return seconds + 's';
            } else if (seconds < 3600) {
                return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
            } else {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                return hours + 'h ' + minutes + 'm';
            }
        },

        // Format coordinates
        formatCoordinates: function(lat, lng) {
            return parseFloat(lat).toFixed(6) + ', ' + parseFloat(lng).toFixed(6);
        }
    };

    // Initialize DataTables
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            pageLength: 50,
            order: [[0, 'desc']],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            }
        });
    }

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);

})();
