/*******************************************************************************
 *
 *  Copyright © 2015 Hewlett-Packard Development Company, L.P.
 *
 *  This work is distributed under the W3C® Software License [1]
 *  in the hope that it will be useful, but WITHOUT ANY
 *  WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231
 *
 ******************************************************************************/


/**
 * UI functionality for the DetailsPage
 */


function removeResult(event)
{
  var button = event.target;
  var row = button.parentNode.parentNode;
  var resultId = row.getAttribute('data-result-id');

  var params = {};
  params['action'] = 'delete';
  params['result'] = resultId;
  system.callAPIPost(gResultsAPIURI, params, null, button);

  var tbody = row.parentNode;
  tbody.removeChild(row);

  if (0 == tbody.getElementsByTagName('tr').length) {
    var table = tbody.parentNode;
    table.removeChild(tbody);

    if (0 == table.getElementsByTagName('tbody').length) {
      table.parentNode.insertBefore(system.createElement('p', null, 'No results entered matching this query.'), table);
      table.parentNode.removeChild(table);
    }
  }
}
