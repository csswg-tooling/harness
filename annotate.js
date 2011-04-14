/*******************************************************************************
 *
 *  Copyright © 2011 Hewlett-Packard Development Company, L.P. 
 *
 *  This work is distributed under the W3C® Software License [1] 
 *  in the hope that it will be useful, but WITHOUT ANY 
 *  WARRANTY; without even the implied warranty of 
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 *  [1] http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 
 *
 *  Adapted from the Mobile Test Harness
 *  Copyright © 2007 World Wide Web Consortium
 *  http://dev.w3.org/cvsweb/2007/mobile-test-harness/
 * 
 ******************************************************************************/

/**
  Data returned from server:
  
  data.anchorName;
  data.testCount;
  data.engines[];
  data.testURI;

  engine.title;
  engine.passCount;
  engine.failCount;
  engine.detailsURI;

**/

var QUERY_URI       = 'http://test.csswg.org/harness/status';
var STYLESHEET_URI  = 'http://test.csswg.org/harness/annotate.css';


function addAnnotationTo(element, data)
{
  if (element) {
    var annotation = document.createElement('div');
    
    annotation.setAttribute('class', 'annotation');
    
    var heading = document.createElement('div');
    var testLink = document.createElement('a');
    testLink.setAttribute('href', data.testURI);
    heading.appendChild(testLink);
    if (1 == data.testCount) {
      testLink.appendChild(document.createTextNode('1 Test'));
    }
    else {
      testLink.appendChild(document.createTextNode(data.testCount + ' Tests'));
    }
    annotation.appendChild(heading);
    
    for (index in data.engines) {
      var engineData = data.engines[index];
      var resultCount = (engineData.passCount + engineData.failCount);
      
      var engineNode = document.createElement('span');
      var toolTip = '';
      var engineClass = '';
      if (0 < resultCount) {
        if (engineData.passCount == data.testCount) {
          toolTip = 'All tests pass';
          engineClass = 'pass';
        }
        else {
          if (engineData.failCount == data.testCount) {
            toolTip = 'All tests fail';
            engineClass = 'fail';
          }
          else {
            if (0 < engineData.passCount) {
              toolTip = engineData.passCount + ' pass';
            }
            if (0 < engineData.failCount) {
              if (toolTip.length) {
                toolTip += ', '
              }
              toolTip += engineData.failCount + ' fail';
            }
            if (resultCount < data.testCount) {
              if (toolTip.length) {
                toolTip += ', '
              }
              toolTip += (data.testCount - resultCount) + ' untested';
            }
            engineClass = 'uncertain';
          }
        }
      }
      else {
        toolTip = 'No data';
      }
      engineNode.setAttribute('title', toolTip);
      engineNode.setAttribute('class', engineClass);

      var detailsLink = document.createElement('a');
      detailsLink.setAttribute('href', engineData.detailsURI);
      
      detailsLink.appendChild(document.createTextNode(engineData.title));
      engineNode.appendChild(detailsLink);
      
      annotation.appendChild(engineNode);
      annotation.appendChild(document.createTextNode(' '));
    }
    
    element.parentNode.insertBefore(annotation, element);
  }
}


function processAnnotation(testData)
{
  var headings = {'h1':'', 'h2':'', 'h3':'', 'h4':'', 'h5':'', 'h6':'',
                  'H1':'', 'H2':'', 'H3':'', 'H4':'', 'H5':'', 'H6':''};
  var anchorName = testData.anchorName;

  if (anchorName) { // find heading that contains anchor
    var anchors = document.getElementsByName(anchorName);
    
    if (anchors && (0 < anchors.length)) {
      var anchor = anchors[0];
      var heading = anchor.parentNode;
      
      while (heading && (Node.ELEMENT_NODE == heading.nodeType) && (! (heading.tagName in headings))) {
        heading = heading.parentNode;
      }
      if (heading && (Node.ELEMENT_NODE == heading.nodeType)) {
        addAnnotationTo(heading, testData);
      }
    }
  }
  else {  // find first h1
    var headings = document.getElementsByTagName('h1');
    
    if (headings && (0 < headings.length)) {
      addAnnotationTo(headings[0], testData);
    }
  }
}


function annotate()
{
  try {
    var testSuiteName = '';
    
    var scripts = document.getElementsByTagName('script');
    for (index in scripts) {
      if (scripts[index].hasAttribute('src')) {
        var scriptSource = scripts[index].getAttribute('src');
        if (-1 < scriptSource.indexOf('harness/annotate.js#')) {
          testSuiteName = scriptSource.substr(scriptSource.indexOf('#') + 1);
          break;
        }
      }
    }
    
    if (0 < testSuiteName.length) {
      var styleSheet = document.createElement('link');
      styleSheet.setAttribute('rel', 'stylesheet');
      styleSheet.setAttribute('type', 'text/css');
      styleSheet.setAttribute('href', STYLESHEET_URI);
      document.getElementsByTagName('head')[0].appendChild(styleSheet)

      var xhr = new XMLHttpRequest();
      
      xhr.onreadystatechange = function() {
        if (4 == xhr.readyState) {
          if (200 == xhr.status) {
            var contentType = xhr.getResponseHeader('Content-Type');
            if (-1 < contentType.indexOf('application/json')) {
              var data = JSON.parse(xhr.responseText);
              
              if (data) {
                if (data instanceof Array) {
                  for (index in data) {
                    processAnnotation(data[index]);
                  }
                }
                else {
                  processAnnotation(data);
                }
              }
            }
          }
          else if (500 == xhr.status) {
  // DEBUG          document.documentElement.innerHTML = xhr.responseText;  // DEBUG
          }
          else {
  // DEBUG          document.body.innerHTML = 'error: ' + xhr.status; // DEBUG
          }
        }
      };
      
      var statusURI = QUERY_URI + '?s=' + encodeURIComponent(testSuiteName) + '&x=' + encodeURIComponent(document.URL);
      xhr.open('GET', statusURI, true);
      xhr.setRequestHeader('Accept', 'application/json,text/html');
      xhr.send();
    }
  }
  catch (err)
  {
// DEBUG    document.body.innerHTML = 'EXCEPTION: ' + err.toString(); // DEBUG
  }
}


function addLoadEvent(loadFunc)
{
  var oldOnLoad = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = loadFunc;
  }
  else {
    window.onload = function () {
      if (oldOnLoad) {
        oldOnLoad();
      }
      loadFunc();
    }
  }
}


addLoadEvent(annotate);

