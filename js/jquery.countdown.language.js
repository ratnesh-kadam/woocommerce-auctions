/* http://keith-wood.name/countdown.html
 * Croatian Latin initialisation for the jQuery countdown extension
 * Written by Dejan Broz info@hqfactory.com (2011) */
(function($) {
    $.SAcountdown.regional['hr'] = {
        labels: [data.labels.Years, data.labels.Months, data.labels.Weeks, data.labels.Days, data.labels.Hours, data.labels.Minutes, data.labels.Seconds],
        labels1: [data.labels1.Year, data.labels1.Month, data.labels1.Week, data.labels1.Day, data.labels1.Hour, data.labels1.Minute, data.labels1.Second],
        
        compactLabels: [data.compactLabels.y, data.compactLabels.m, data.compactLabels.w, data.compactLabels.d],
        whichLabels: function(amount) {
            return (amount == 1 ? 1 : (amount >= 2 && amount <= 4 ? 2 : 0));
        },
        digits: ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        timeSeparator: ':', isRTL: false};
    $.SAcountdown.setDefaults($.SAcountdown.regional['hr']);
})(jQuery);
