/**
 * GateSpark Reports JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        /**
         * Initialize Revenue Chart
         */
        if (typeof Chart !== 'undefined' && $('#gatespark-revenue-chart').length) {
            var ctx = document.getElementById('gatespark-revenue-chart').getContext('2d');
            
            var chartData = gatesparkReports.chartData;
            
            // Create gradient
            var gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(102, 126, 234, 0.2)');
            gradient.addColorStop(1, 'rgba(102, 126, 234, 0)');
            
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: chartData.values,
                        borderColor: '#667eea',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#667eea',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: 'USD'
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderColor: '#667eea',
                            borderWidth: 1,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(0);
                                },
                                color: '#6b7280',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
            
            // Animate on load
            setTimeout(function() {
                chart.update('active');
            }, 100);
        }
        
        /**
         * Export CSV with nonce
         */
        $(document).on('click', '#gatespark-export-csv', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var period = new URLSearchParams(window.location.search).get('period') || '30days';
            
            // Show loading state
            var originalHtml = $button.html();
            $button.html('<span class="dashicons dashicons-update dashicons-spin"></span> Exporting...').prop('disabled', true);
            
            // Trigger download with nonce
            var url = gatesparkReports.ajaxUrl + 
                '?action=gatespark_export_csv' +
                '&nonce=' + encodeURIComponent(gatesparkReports.nonce) + 
                '&period=' + encodeURIComponent(period);
            
            window.location.href = url;
            
            // Reset button after delay
            setTimeout(function() {
                $button.html(originalHtml).prop('disabled', false);
            }, 2000);
        });
        
        /**
         * Animate stat cards on scroll
         */
        if ('IntersectionObserver' in window) {
            var statCards = document.querySelectorAll('.gatespark-stat-card');
            
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry, index) {
                    if (entry.isIntersecting) {
                        setTimeout(function() {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            statCards.forEach(function(card) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                observer.observe(card);
            });
        }
        
        /**
         * Refresh stats button (future feature)
         */
        $(document).on('click', '.gatespark-refresh-stats', function(e) {
            e.preventDefault();
            location.reload();
        });
        
        /**
         * Period switcher smooth transition
         */
        $('.gatespark-period-selector .period-button').on('click', function() {
            if (!$(this).hasClass('active')) {
                $('body').css('opacity', '0.8');
            }
        });
        
        /**
         * Table row click (navigate to order)
         */
        $('.gatespark-transactions-container tbody tr').on('click', function(e) {
            if (!$(e.target).is('a')) {
                var link = $(this).find('.order-link').attr('href');
                if (link) {
                    window.location.href = link;
                }
            }
        });
        
    });

})(jQuery);
