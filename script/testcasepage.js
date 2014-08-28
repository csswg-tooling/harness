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
 * UI functionality for the TestCasePage
 */


function switchToTab(referenceName)
{
  var tabGroup = document.getElementById('reference_tabs');
  
  if (tabGroup) {
    var tabs = system.findChildWithClass(tabGroup, 'tabs');
    system.iterateElementChildren(tabs, function (child, index) {
      if (child.getAttribute('data-ref') == referenceName) {
        system.addClass(child, 'active');
      }
      else {
        system.removeClass(child, 'active');
      }
    });
    
    var tabFoot = system.findChildWithClass(tabGroup, 'tab_foot');
    system.iterateElementChildren(tabFoot, function (child, index) {
      if (child.getAttribute('data-ref') == referenceName) {
        system.addClass(child, 'active');
      }
      else {
        system.removeClass(child, 'active');
      }
    });
  }
    
  var testWrapper = document.getElementById('test_wrapper');
  system.iterateElementChildren(testWrapper, function (child, index) {
    if (child.getAttribute('data-ref') == referenceName) {
      system.addClass(child, 'active');
    }
    else {
      system.removeClass(child, 'active');
    }
  });
}

function activateReferenceTab(domEvent)
{
  setAutoCycle(false);
  
  var tab = domEvent.target.parentNode;
  var refName = tab.getAttribute('data-ref');
  switchToTab(refName);
  
  (domEvent.preventDefault) ? domEvent.preventDefault() : domEvent.returnValue = false;
}

var cycleInterval = null;
var tabNames = null;
var tabIndex = -1;

function cycleTab()
{
  if (++tabIndex >= tabNames.length) {
    tabIndex = 0;
  }
  switchToTab(tabNames[tabIndex]);
}

function setAutoCycle(state)
{
  if (state) {
    if (! cycleInterval) {
      cycleInterval = setInterval(cycleTab, 200);
    }
  }
  else {
    if (cycleInterval) {
      clearInterval(cycleInterval);
      cycleInterval = null;
    }
  }

  document.getElementById('cycle_button').checked = state;
  system.setCookie('autocycle', (state ? 'true' : null));
}


function computeResult(testStatus, testResults)
{
  overallResult = 'pass';
  
  var passCount = 0;
  var failCount = 0;
  
  if ((testStatus.ERROR === testStatus.status) || 
      (testStatus.TIMEOUT === testStatus.status) ||
      (null === testResults) ||
      ('object' !== typeof(testResults))) {
    overallResult = 'cannot';
  }
  else {
    for (var index = 0; index < testResults.length; index++) {
      if (null === testResults[index].status) {
        overallResult = 'cannot';
      }
      else if (testResults[index].PASS === testResults[index].status) {
        passCount++;
      }
      else if (testResults[index].FAIL === testResults[index].status) {
        failCount++;
        if ('pass' == overallResult) {
          overallResult = 'fail';
        }
      }
      else if (testResults[index].NOTRUN === testResults[index].status) {
        overallResult = 'skip';
      }
    }
  }
  
  document.getElementById('pass_count').value = passCount;
  document.getElementById('fail_count').value = failCount;
}

function submitResults()
{
  if (overallResult) {
    if (document.getElementById('auto_submit_button').checked) {
      document.getElementById('button_' + overallResult).click();
    }
    else {
      document.getElementById('button_' + overallResult).focus();
    }
  }
}

function testHarnesMessage(event)
{
  if ('complete' == event.data.type) {
    computeResult(event.data.status, event.data.tests);
    
    submitResults();
  }
}

function setAutoSubmit(state)
{
  document.getElementById('auto_submit_button').checked = state;
  system.setCookie('autosubmit', (state ? 'true' : null));

  if (state) {
    submitResults();
  }
}

function setupPage()
{
  var tabGroup = document.getElementById('reference_tabs');
  
  if (tabGroup) {
    var tabs = system.findChildWithClass(tabGroup, 'tabs');
    tabNames = [];
    system.iterateElementChildren(tabs, function (child, index) {
      var link = system.getFirstElementChild(child);
      if (link) {
        tabNames.push(child.getAttribute('data-ref'));
        link.onclick = activateReferenceTab;
        link.removeAttribute('href');
      }
    });
    
    var cycle = system.createElement('span', { 'class': 'cycle' });
    var label = system.createElement('label', { 'for': 'cycle_button' }, 'Auto Cycle');
    var cycleButton = system.createElement('input', { 'type': 'checkbox', 'id': 'cycle_button' });
    cycleButton.onclick = function (domEvent) { setAutoCycle(domEvent.target.checked) };
    cycle.appendChild(label);
    cycle.appendChild(cycleButton);
    tabGroup.insertBefore(cycle, tabs);
    
    if (system.getCookie('autocycle')) {
      setAutoCycle(true);
    }
  }
  
  var skipButton = document.getElementById('button_skip');
  if (skipButton) {
    skipButton.parentNode.appendChild(document.createTextNode(' '));
    var label = system.createElement('label', { 'for': 'auto_submit_button' }, 'Auto Submit');
    var submitButton = system.createElement('input', { 'type': 'checkbox', 'id': 'auto_submit_button', 'name': 'auto', 'value': 1 });
    submitButton.onclick = function (domEvent) { setAutoSubmit(domEvent.target.checked); };
    
    skipButton.parentNode.appendChild(label);
    skipButton.parentNode.appendChild(submitButton);
    if (system.getCookie('autosubmit')) {
      setAutoSubmit(true);
    }
  }
  
}

var overallResult = null;

if (window.addEventListener) {
  addEventListener("message", testHarnesMessage, false);
}
else {
  attachEvent("onmessage", testHarnesMessage);
}

system.addLoadEvent(setupPage);
