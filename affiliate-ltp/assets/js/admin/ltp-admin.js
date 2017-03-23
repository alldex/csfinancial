(function($, angular) {
    
    function getAutocompleteLinker(name, service, eventName) {
        return function (scope, elem, attr, ctrl) {
            var autoCompleteIndex = null;
            
            if (attr.hasOwnProperty(name + "Index")) {
                attr.$observe(name + "Index", function(value) {
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
                position: { offset: '0, -1' },
                minLength: 3,
                open: function() {
                        elem.addClass( 'open' );
                },
                close: function() {
                        elem.removeClass( 'open' );
                }
                ,select: function (event, selectedItem) {
                    
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
     * @param object obj1
     * @param object obj2
     * @returns object
     */
    function fillObject(obj1, obj2) {
        for (var index in obj1) {
            if (obj2.hasOwnProperty(index)
                    && obj1.hasOwnProperty(index)) {
                obj1[index] = obj2[index];
            }
        }
        return obj1;
    }
    
    angular.module('commissionsApp', [])
            .factory("CommissionService", ["$http", function($http) {
                    return {
                        save: function (data) {
                            return $http.post(window.location.href).then(function (response) {
                                return response.data;
                            });
                        }
                    };
            }])
            .factory("AgentAutoCompleteService", ["$http", function($http) {
                    return {
                        search: function (term) {
                            return $http.get(ajaxurl + '?action=affwp_search_users&status=active&term=' + term).then(function (response) {
                                return response.data;
                            });
                        }
                    };
                    
            }])
            .factory("ClientAutoCompleteService", ["$http", function($http) {
                    return {
                        search: function (term) {
                            return $http.get(ajaxurl + '?action=affwp_ltp_search_clients&term=' + term).then(function (response) {
                                return response.data;
                            });
                        }
                    };
                    
            }])
            .directive("ltpAgentAutocomplete", ["AgentAutoCompleteService", function (AgentAutoCompleteService) {
                return {
                    restrict: "A"
                    ,scope: {
                        ngModel: '=ngModel'
                        ,autocompleteType: '&autocompleteType'
                        ,autocompleteIndex: '&autocompleteIndex'
                    }
                    ,link: getAutocompleteLinker("ltpAgentAutocomplete", AgentAutoCompleteService, 'agent.autocomplete.selected')
                };
            }])
            .directive("ltpClientAutocomplete", ["ClientAutoCompleteService", function (ClientAutoCompleteService) {
            return {
                restrict: "A"
                ,scope: {
                    ngModel: '=ngModel'
                    ,autocompleteType: '&autocompleteType'
                    ,autocompleteIndex: '&autocompleteIndex'
                }
                ,link: getAutocompleteLinker("ltpClientAutocomplete", ClientAutoCompleteService, 'client.autocomplete.selected') 
            };
        }])
        .controller('CommissionAddController', ['$scope', 'CommissionService', function($scope, CommissionService) {
        

        // handle when a client is selected.
        $scope.$on('client.autocomplete.selected', function(event, client) {            
            fillObject(commissionsAdd.client, client);
            
            if (commissionsAdd.client.id) {
                commissionsAdd.readonlyClient = true;
            }
        });
        
        // handle when an agent is selected
        $scope.$on('agent.autocomplete.selected', function(event, agent, type, agentIndex) {
            if (type === 'writing') {
                commissionsAdd.commission.writing_agent.id = agent.user_id;
                commissionsAdd.commission.writing_agent.name = agent.value;
            }
            else {
                if (commissionsAdd.commission.split_agents[agentIndex]) {
                    commissionsAdd.commission.split_agents[agentIndex].id = agent.user_id;
                    commissionsAdd.commission.split_agents[agentIndex].name = agent.value;
                }
            };
        });

        function Client() {
            this.contract_number = null;
            this.name = null;
            this.id = null;
            this.street_address = null; // client_street_address
            this.city = null; // client_city_address
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
            
            this.getSplitTotal = function() {
                var total = !isNaN(+this.writing_agent.split) ? +this.writing_agent.split : 0;
                this.split_agents.forEach(function(agent) { total += !isNaN(+agent.split) ? +agent.split : 0 });
                return total;
            };
            
            this.getSplitKey = function(agentSplitIndex) {
                return "split:" + agentSplitIndex;
            };
        }
            
          var commissionsAdd = this;
          commissionsAdd.readonlyClient = false;
          commissionsAdd.commission = new Commission();
          commissionsAdd.splitTotalInvalid = false;
          commissionsAdd.nonce = null;
          
          commissionsAdd.client = new Client();
         
          commissionsAdd.resetClient = function() {
              commissionsAdd.client = new Client();
              commissionsAdd.readonlyClient = false;
              commissionsAdd.commission = new Commission();
          };
          
          commissionsAdd.isSplit = function() {
              return commissionsAdd.commission.split_commission;
          };
          commissionsAdd.isLifePolicy = function() {
              return commissionsAdd.commission.is_life_commission;
          };
          commissionsAdd.toggleSplit = function() {
              commissionsAdd.commission.split_commission = !commissionsAdd.commission.split_commission;
          };
          commissionsAdd.toggleLifePolicy = function() {
              commissionsAdd.commission.is_life_commission = !commissionsAdd.commission.is_life_commission;
          };
          
          commissionsAdd.addSplit = function() {
              commissionsAdd.commission.split_agents.push(new Agent());
          };
          
          commissionsAdd.removeSplit = function(split) {
              var index = commissionsAdd.commission.split_agents.indexOf(split);
              if (index >= 0) {
                  commissionsAdd.commission.split_agents.splice(index, 1);
              }
          };
          
          commissionsAdd.getSplitTotal = function() {
              return commissionsAdd.commission.getSplitTotal();;
          };
          
          commissionsAdd.isSplitTotalInvalid = function() {
              return commissionsAdd.commission.getSplitTotal() !== 100;
          };
          
          commissionsAdd.save = function() {
            alert("clicked on submit button");
            
            var commission = commissionsAdd.commission;
            // need to convert the data here.
            var agents = [].concat(commission.writing_agent, commission.agents);
            var data = {
                client: commissionsAdd.client
                ,cb_is_life_commission: commission.is_life_commission
                ,cb_split_commission: commission.split_commission
                ,cb_skip_company_haircut: commission.skip_company_haircut
                ,cb_company_haircut_all: commission.company_haircut_all
                ,agents: agents
                // we still have to go and fetch the existing record.
                // is this a new record or an existing record
                ,new_business: commissionsAdd.readonlyClient
                ,action: "add_referral"
                ,'affwp_add_referral_nonce': commissionsAdd.nonce
            };
            console.log("saving data", data);
            
            CommissionService.save(data).then(function(data) {
                if (data.type === 'success') {
                    window.location.href = data.redirect;
                }
                else if (data.type === 'error') {
                    // TODO: stephen need to drill down into the error
                    alert("an error occurred: " + data.message);
                }
            })
            .catch(function(error) {
                alert("An error occurred in communicating with the server.  Please try again.");
                console.log(error);
                // TODO: stephen handle this better
            });
          };
          commissionsAdd.searchCommission = function() {
              
          };
    }]);
    
    var rowId = 1;

    function setupAgentSearch(selector) {
        		var	$this    = $( selector ),
			$action  = 'affwp_search_users',
			$search  = $this.val(),
			$status  = $this.data( 'affwp-status'),
			$agent_id = $this.siblings(".agent-id");

		$this.autocomplete( {
			source: ajaxurl + '?action=' + $action + '&term=' + $search + '&status=' + $status,
			delay: 500,
			minLength: 2,
			position: { offset: '0, -1' },
			select: function( event, data ) {
				$agent_id.val( data.item.user_id );
			},
			open: function() {
				$this.addClass( 'open' );
			},
			close: function() {
				$this.removeClass( 'open' );
			}
		} );

		// Unset the user_id input if the input is cleared.
		$this.on( 'keyup', function() {
			if ( ! this.value ) {
				$agent_id.val( '' );
			}
		} );
    }
    // allows us to change this around if needed.
    function getRowId() {
        return rowId++;
    }
    
    function setupAddReferralScreen() {
       
//        setupAgentSearch("#affwp_add_referral .commission_row_single .affwp-agent-search");
    }
    
    function setupAgentScreen() {
        setupAgentSearch("#affwp_add_affiliate .affwp-agent-search");
        setupAgentSearch("#affwp_edit_affiliate .affwp-agent-search");
    }
    
    function setupCommissionScreen() {
        $("#affwp_add_referral #submit").click(function(evt) {
            evt.preventDefault();
            evt.stopPropagation();
            alert("clicked on submit button");
            
            var data = $("#affwp_add_referral").serialize();
            
            $.post(window.location, data, function(data, success) {
                if (!data || !success) {
                    alert("An error occurred in communicating with the server.  Please try again.");
                    return;
                }
                if (data.type === 'success') {
                    window.location.href = data.redirect;
                }
                else if (data.type == 'error') {
                    // TODO: stephen need to drill down into the error
                    alert("an error occurred: " + data.message);
                }
            }, 'json');
            
            return false;
        });
    }
    
    $(document).ready(function() {
        setupAddReferralScreen();
        setupAgentScreen();
        setupCommissionScreen();
    });
})(jQuery, angular);