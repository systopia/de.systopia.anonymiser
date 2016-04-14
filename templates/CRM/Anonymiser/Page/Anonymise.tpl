{*-------------------------------------------------------+
| SYSTOPIA Contact Anonymiser Extension                  |
| Copyright (C) 2016 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+-------------------------------------------------------*}

<p>{ts domain=de.systopia.anonymiser}Warning!{/ts}</p>
<p>
  {ts domain=de.systopia.anonymiser 1=$contact}You are about to anonymise the contact %1. This will anonymise statistically relevant data and delete everything else.{/ts}
  {ts domain=de.systopia.anonymiser}This process can not be reversed!{/ts}
  {ts domain=de.systopia.anonymiser}Please note that the data might still be present in old backups.{/ts}
</p>

<div class="anonymise-question">
  <p>
    {ts domain=de.systopia.anonymiser}Are you sure you want to anonymise this contact?{/ts}
  </p>
  <div class="ui-dialog-buttonset">
    <button type="button" id="anonymise-contact-button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button"><span class="ui-button-icon-primary ui-icon ui-icon-check"></span><span class="ui-button-text">{ts domain=de.systopia.anonymiser}YES!{/ts}</span></button>
  </div>
</div>

<div class="anonymise-log" style="display: none;">
  <p id="anonymiser-status-text">
    <img id="bic_busy" height="12" src="{$config->resourceBase}i/loading.gif"/>
    {ts domain=de.systopia.anonymiser 1=$contact}Anonymising contact %1...{/ts}
  </p>
  <h3>{ts domain=de.systopia.anonymiser 1=$contact}Log:{/ts}</h3>
  <ul id="anonymiser-log-content">
  </ul>
</div>


<script type="text/javascript">
var success_message = "<b>{ts domain=de.systopia.anonymiser 1=$contact}Contact %1 successfully anonymised.{/ts}</b>";

{literal}
cj("#anonymise-contact-button").click(function() {
  cj("div.anonymise-question").hide(500);
  cj("div.anonymise-log").show(500);

  // call the API
  CRM.api('Contact', 'anonymise', {'contact_id': 5},
    { // SUCCESS HANDLER
      success: function(data) {
        // first: replace status text
        cj("#anonymiser-status-text").text(success_message);

        // then add the log items to the log
        var log_entries = data.values;
        for (var i = 0; i < log_entries.length; i++) {
          cj("#anonymiser-log-content").append("<li>" + log_entries[i] + "</li>");
        };
      }
    });

  // reload page on close
  cj(document).on('crmPopupClose', function() {
    window.location.reload();
  });
});
</script>
{/literal}