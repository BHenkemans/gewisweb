Company = {};

Company.Admin = {
    init: function () {
        $('#filterCompanies').keyup(function() {
            filterCompanies($(this).val());
        });
    },

    filterCompanies: function () {
        var term = $('#filterCompanies').val();
        $('.company-list-item').each(function() {
            if ($(this).text().toLowerCase().indexOf(term.toLowerCase()) === -1) {
                $(this).css({display: 'none'});
            } else {
                $(this).css({display: 'table-row'});
            }
        });
    },

    sortCompaniesByName: function(order) {
        var companies = $('.company-list-item');
        companies.sort(function(a,b) {

            if ($(a).find('.company-name > a').html() > $(b).find('.company-name > a').html()) {
                return order;
            } else {
                return -1 * order;
            }

        });
        companies.detach().appendTo($(".company-list"));
    },

    sortCompaniesByColumn: function(order, column) {
        var companies = $('.company-list-item');
        companies.sort(function(a,b) {
            console.log($($(a).find('td')[column]).html());
            if ($($(a).find('td')[column]).html() > $($(b).find('td')[column]).html()) {
                return order;
            } else {
                return -1 * order;
            }

        });
        companies.detach().appendTo($('.company-list'));
    },

    deleteCompany: function (slugCompanyName) {
        $("#deleteForm").attr('action', URLHelper.url('company_admin/company/delete', {'slugCompanyName': slugCompanyName}));
        $("#deleteCompanyName").html(slugCompanyName);
    },

    deletePackage: function (slugCompanyName, packageId) {
        $("#deleteForm").attr('action', URLHelper.url('company_admin/company/edit/package/delete', {
            'slugCompanyName': slugCompanyName,
            'packageId': packageId
        }));
    }
};
