(function ($, angular) {
    function Client() {
        this.id = null;
        this.contract_number = null;
        this.name = null;
        this.street_address = null; // client_street_address
        this.city = null; // client_city_address
        this.state = null; // client_state_address
        this.state_of_sale = null; // client_state_of_sale
        this.zip = null; // client_zip_address
        this.phone = null; // client_phone
        this.email = null; // client_email
    }

    function Agent() {
        this.id = null;
        this.name = null;
        this.split = 0;
    }

    function Commission() {
        this.is_life_commission = false;
        this.split_commission = false;
        this.writing_agent = new Agent();
        this.writing_agent.split = 100;
        this.split_agents = [];
        this.skip_company_haircut = false;
        this.company_haircut_all = false;
        this.date = null;
        this.points = 0;
        this.amount = 0;
        this.new_business = true;
        this.haircut_percent = 15; // default company haircut percent
        
        this.getSplitTotal = function () {
            var total = !isNaN(+this.writing_agent.split) ? +this.writing_agent.split : 0;
            this.split_agents.forEach(function (agent) {
                total += !isNaN(+agent.split) ? +agent.split : 0
            });
            return total;
        };
    }

    function CommissionAddController($scope, CommissionService) {

        var commissionsAdd = this;
        commissionsAdd.readonlyClient = false;
        commissionsAdd.commission = new Commission();
        commissionsAdd.splitTotalInvalid = false;
        commissionsAdd.saleOriginatedOutOfState = false;
        commissionsAdd.nonce = null;
        commissionsAdd.skip_life_licensed_check = false;
        commissionsAdd.client = new Client();
        commissionsAdd.haircut_percent_list = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];
        commissionsAdd.life_policy_default_haircut = 10;
        commissionsAdd.non_life_policy_default_haircut = 15;
        
        

        // handle when a client is selected.
        $scope.$on('client.autocomplete.selected', function (event, client) {
            fillObject(commissionsAdd.client, client);

            if (commissionsAdd.client.id) {
                commissionsAdd.readonlyClient = true;
            }
            if (commissionsAdd.client.state !== commissionsAdd.client.state_of_sale) {
                commissionsAdd.saleOriginatedOutOfState = true;
            }
            
            CommissionService.findCommissionByContractNumber(client.contract_number)
                    .then(function(commissionResult) {
                        commissionsAdd.populateCommission(commissionResult.data);
                    })
                    .catch(function(error) {
                        // TODO: stephen change to something better than alerts
                        if (error && error.data) {
                            // we don't care about 404's as that is our not found message.
                            if (error.status !== 404 && error.data.message) {
                                alert("A server error occurred. Error: " + error.data.message);
                            }
                        }
                        else {
                            alert("A server error occurred and we could not check for repeat business with this contract number");
                        }
            });
        });

        // handle when an agent is selected
        $scope.$on('agent.autocomplete.selected', function (event, agent, type, agentIndex) {
            if (type === 'writing') {
                commissionsAdd.commission.writing_agent.id = agent.user_id;
                commissionsAdd.commission.writing_agent.name = agent.value;
            }
            else {
                if (commissionsAdd.commission.split_agents[agentIndex]) {
                    commissionsAdd.commission.split_agents[agentIndex].id = agent.user_id;
                    commissionsAdd.commission.split_agents[agentIndex].name = agent.value;
                }
            }
        });
        
        commissionsAdd.populateCommission = function(popCommission) {
             commissionsAdd.commission.new_business = false; // if we are populating it's not new_business
             commissionsAdd.commission.is_life_commission = popCommission.is_life_commission;
             commissionsAdd.commission.split_commission = popCommission.split_commission;
             commissionsAdd.commission.writing_agent = popCommission.writing_agent;
             commissionsAdd.commission.split_agents = popCommission.agents ? popCommission.agents : [];
             commissionsAdd.updateHaircutPercent();
        };

        commissionsAdd.resetClient = function () {
            commissionsAdd.client = new Client();
            commissionsAdd.readonlyClient = false;
            commissionsAdd.commission = new Commission();
            commissionsAdd.commission.new_business = true;
        };

        commissionsAdd.isSplit = function () {
            return commissionsAdd.commission.split_commission;
        };
        commissionsAdd.isLifePolicy = function () {
            return commissionsAdd.commission.is_life_commission;
        };
        commissionsAdd.toggleSplit = function () {
            commissionsAdd.commission.split_commission = !commissionsAdd.commission.split_commission;
        };
        commissionsAdd.toggleLifePolicy = function () {
            commissionsAdd.commission.is_life_commission = !commissionsAdd.commission.is_life_commission;
            commissionsAdd.updateHaircutPercent();
        };
        
        commissionsAdd.updateHaircutPercent = function() {
            if (commissionsAdd.commission.is_life_commission) {
                commissionsAdd.commission.haircut_percent = commissionsAdd.life_policy_default_haircut;
            }
            else {
                commissionsAdd.commission.haircut_percent = commissionsAdd.non_life_policy_default_haircut;
            }
        };

        commissionsAdd.addSplit = function () {
            commissionsAdd.commission.split_agents.push(new Agent());
        };

        commissionsAdd.removeSplit = function (split) {
            var index = commissionsAdd.commission.split_agents.indexOf(split);
            if (index >= 0) {
                commissionsAdd.commission.split_agents.splice(index, 1);
            }
        };

        commissionsAdd.getSplitTotal = function () {
            return commissionsAdd.commission.getSplitTotal();
            ;
        };

        commissionsAdd.isSplitTotalInvalid = function () {
            return commissionsAdd.commission.getSplitTotal() !== 100;
        };
        
        commissionsAdd.isRepeatBusiness = function() {
            return !(commissionsAdd.commission.new_business);
        };

        commissionsAdd.save = function () {
            var commission = commissionsAdd.commission;
            // need to convert the data here.
            var agents = [].concat(commission.writing_agent, commission.split_agents);
            
            // if the state is the same, make the state_of_sale the same.
            if (!commissionsAdd.saleOriginatedOutOfState) {
                commissionsAdd.client.state_of_sale = commissionsAdd.client.state;
            }

            var data = {
                client: commissionsAdd.client
                , is_life_commission: commission.is_life_commission
                , split_commission: commission.split_commission
                , skip_company_haircut: commission.skip_company_haircut
                , company_haircut_all: commission.company_haircut_all
                , agents: agents
                , date: commission.date
                , points: commission.points
                , amount: commission.amount
                        // we still have to go and fetch the existing record.
                        // is this a new record or an existing record
                , new_business: commissionsAdd.commission.new_business
                , action: "add_referral"
                , 'affwp_add_referral_nonce': commissionsAdd.nonce
                , skip_life_licensed_check: commissionsAdd.skip_life_licensed_check
                , company_haircut_percent: commission.company_haircut_all ? 100 : commission.haircut_percent
            };
            
            CommissionService.save(data).then(function (data) {
                console.log(data);
                if (data.type === 'success') {
                    window.location.href = data.redirect;
                }
                else if (data.type === 'error') {
                    // TODO: stephen need to drill down into the error
                    alert("an error occurred: " + data.message);
                }
                else if (data.type === 'validation') {
                    var message = "One or more agents do not have active life insurance licenses, do you still want to continue";
                    if (confirm(message)) {
                        commissionsAdd.skip_life_licensed_check = true;
                        commissionsAdd.save(); // save again.
                    }   
                }
            })
            .catch(function (error) {
                alert("An error occurred in communicating with the server.  Please try again.");
                console.log(error);
                // TODO: stephen handle this better
            });
        };
    }

    function getAutocompleteLinker(name, service, eventName) {
        return function (scope, elem, attr, ctrl) {
            var autoCompleteIndex = null;

            if (attr.hasOwnProperty(name + "Index")) {
                attr.$observe(name + "Index", function (value) {
                    autoCompleteIndex = value;
                });
            }


            elem.autocomplete({
                source: function (searchTerm, response) {
                    service.search(searchTerm.term).then(function (autocompleteResults) {
                        response(autocompleteResults);
                    });
                },
                delay: 500,
                position: {offset: '0, -1'},
                minLength: 3,
                open: function () {
                    elem.addClass('open');
                },
                close: function () {
                    elem.removeClass('open');
                }
                , select: function (event, selectedItem) {

                    // Do something with the selected item, e.g. 
                    var type = attr[name] ? attr[name] : null;
                    scope.ngModel = selectedItem.item.value;
                    scope.$emit(eventName, selectedItem.item, type, autoCompleteIndex);
                    scope.$apply();
                    event.preventDefault();
                }
            });
        }
    }

    /**
     * Takes all of the values in obj2 that have a corresponding key in obj1
     * and overwrites the obj1 value
     * @param object dest
     * @param object src
     * @returns object
     */
    function fillObject(dest, src) {
        for (var index in dest) {
            if (src.hasOwnProperty(index)
                    && dest.hasOwnProperty(index)) {
                dest[index] = src[index];
            }
        }
        return dest;
    }

    angular.module('commissionsApp', [])
            .factory("CommissionService", ["$http", function ($http) {
                    return {
                        save: function (data) {
                            return $http.post(ajaxurl + '?action=affwp_add_referral', data).then(function (response) {
                                return response.data;
                            });
                        }
                        ,findCommissionByContractNumber: function(contractNumber) {
                            var encodedNumber = encodeURIComponent(contractNumber);
                            return $http.get(ajaxurl + '?action=affwp_search_commission&contract_number=' + encodedNumber)
                                    .then(function (response) {
                                        return response.data;
                            });
                        }
                    };
                }])
            .factory("AgentAutoCompleteService", ["$http", function ($http) {
                    return {
                        search: function (term) {
                            var encodedTerm = encodeURIComponent(term);
                            return $http.get(ajaxurl + '?action=affwp_search_users&status=active&term=' + encodedTerm).then(function (response) {
                                return response.data;
                            });
                        }
                    };

                }])
            .factory("ClientAutoCompleteService", ["$http", function ($http) {
                    return {
                        search: function (term) {
                            var encodedTerm = encodeURIComponent(term);
                            return $http.get(ajaxurl + '?action=affwp_ltp_search_clients&term=' + encodedTerm).then(function (response) {
                                return response.data;
                            });
                        }
                    };

                }])
            .directive("ltpAgentAutocomplete", ["AgentAutoCompleteService", function (AgentAutoCompleteService) {
                    return {
                        restrict: "A"
                        , scope: {
                            ngModel: '=ngModel'
                            , autocompleteType: '&autocompleteType'
                            , autocompleteIndex: '&autocompleteIndex'
                        }
                        , link: getAutocompleteLinker("ltpAgentAutocomplete", AgentAutoCompleteService, 'agent.autocomplete.selected')
                    };
                }])
            .directive("ltpClientAutocomplete", ["ClientAutoCompleteService", function (ClientAutoCompleteService) {
                    return {
                        restrict: "A"
                        , scope: {
                            ngModel: '=ngModel'
                            , autocompleteType: '&autocompleteType'
                            , autocompleteIndex: '&autocompleteIndex'
                        }
                        , link: getAutocompleteLinker("ltpClientAutocomplete", ClientAutoCompleteService, 'client.autocomplete.selected')
                    };
                }])
            .directive('ltpDatePicker', function () {
                return {
                    restrict: "A"
                    , scope: {
                        ngModel: '=ngModel'
                    }
                    , link: function (scope, elem, attr, ctrl) {
                        elem.datepicker({
                            onSelect: function (value, input) {
                                // it seems we must already be in a digest cycle
                                // as the ngModel assignment doesn't rebind
                                // until the NEXT cycle, we run an apply
                                // in order to make the change immediate.
                                // TODO: stephen there might be a better place
                                // to make this change propigate instead of forcing
                                // an apply, I might be doing the order of ops
                                // wrong for angular here.
                                scope.ngModel = value;
                                scope.$apply();
                            }
                        });
                    }
                };
            })
            .controller('CommissionAddController', ['$scope', 'CommissionService', CommissionAddController]);

    var rowId = 1;

    function setupAgentSearch(selector) {
        var $this = $(selector),
                $action = 'affwp_search_users',
                $search = $this.val(),
                $status = $this.data('affwp-status'),
                $agent_id = $this.siblings(".agent-id");

        $this.autocomplete({
            source: ajaxurl + '?action=' + $action + '&term=' + $search + '&status=' + $status,
            delay: 500,
            minLength: 2,
            position: {offset: '0, -1'},
            select: function (event, data) {
                $agent_id.val(data.item.user_id);
            },
            open: function () {
                $this.addClass('open');
            },
            close: function () {
                $this.removeClass('open');
            }
        });

        // Unset the user_id input if the input is cleared.
        $this.on('keyup', function () {
            if (!this.value) {
                $agent_id.val('');
            }
        });
    }
    
    function setupCommissionListActions() {
        $(".referrals .row-actions a.chargeback").click(function(event) {
           if (!confirm("Are you sure that you want to issue this chargeback?")) {
               event.preventDefault();
               event.stopPropagation();
           } 
        });
        
        $(".referrals .row-actions a.delete").click(function(event) {
           if (!confirm("Are you sure that you wish to delete this commission?")) {
               event.preventDefault();
               event.stopPropagation();
           } 
        });
    }

    function setupAgentScreen() {
        setupAgentSearch("#affwp_edit_affiliate .affwp-agent-search");
        setupCommissionListActions();
    }

    $(document).ready(function () {
        setupAgentScreen();
    });
})(jQuery, angular);