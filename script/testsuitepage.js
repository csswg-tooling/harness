/*******************************************************************************
 *
 *  Copyright © 2014 Hewlett-Packard Development Company, L.P.
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
 * UI functionality for the TestSuitePage
 */



function setAutoSubmit(state)
{
  document.getElementById('auto_submit_button').checked = state;
  system.setCookie('autosubmit', (state ? 'true' : null));
}

function setupPage()
{
  var controls = document.getElementById('extra_controls');
  if (controls) {
    var p = system.createElement('p');
    var label = system.createElement('label', { 'for': 'auto_submit_button' }, 'Automatically submit results when possible');
    var submitButton = system.createElement('input', { 'type': 'checkbox', 'id': 'auto_submit_button', 'name': 'auto', 'value': 1 });
    submitButton.onclick = function (domEvent) { setAutoSubmit(domEvent.target.checked); }
    
    p.appendChild(submitButton);
    p.appendChild(label);
    controls.appendChild(p);
    if (system.getCookie('autosubmit')) {
      setAutoSubmit(true);
    }
  }
  
}

system.addLoadEvent(setupPage);
