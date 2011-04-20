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
  data.needData;

  engine.title;
  engine.passCount;
  engine.failCount;
  engine.detailsURI;

**/

<?php require_once('lib/Page.php'); ?>

var QUERY_URI       = "<?php echo Page::_BuildURI('status', null, null, TRUE); ?>";
var STYLESHEET_URI  = "<?php echo Page::_BuildURI('annotate.css', null, null, TRUE); ?>";
var NEED_TEST_URI   = "<?php echo Page::_BuildURI('img/please_help_32.png', null, null, TRUE); ?>";

var annotationData;
var annotationsClosed = false;


function removeAnnotation(anchorName)
{
  try {
    var annotation = document.getElementById('annotation_' + ((0 < anchorName.length) ? anchorName : 'root_'));

    if (annotation) {
      annotation.parentNode.removeChild(annotation);
    }
  }
  catch (err) {
  }
}

function removeAllAnnotations()
{
  try {
    if (annotationData) {
      if (annotationData instanceof Array) {
        for (index in annotationData) {
          removeAnnotation(annotationData[index].anchorName);
        }
      }
      else {
        removeAnnotation(annotationData.anchorName);
      }
    }
  }
  catch (err) {
  }
}

function toggleAnnotations()
{
  annotationsClosed = (! annotationsClosed);
  removeAllAnnotations();
  addAnnotations();
}


function addAnnotationTo(element, data, first)
{
  try {
    if (element) {
      var annotation = document.createElement('div');
      annotation.setAttribute('id', 'annotation_' + ((0 == data.anchorName.length) ? 'root_' : data.anchorName));
      var annotationClass = 'annotation';
      if (first) {
        annotationClass += ' first';
      }
      if (0 < data.needData) {
        annotationClass += ' need';
      }
      if (annotationsClosed) {
        annotationClass += ' closed';
      }
      annotation.setAttribute('class', annotationClass);

      if (first) {
        var disclosure = document.createElement('div');
        disclosure.setAttribute('class', 'disclosureBox');
        disclosure.setAttribute('onclick', 'toggleAnnotations()');
        annotation.appendChild(disclosure);
      }
      
      var closeBox = document.createElement('div');
      closeBox.setAttribute('class', 'closeBox');
      if (first) {
        closeBox.setAttribute('onclick', 'removeAllAnnotations()');
      }
      else {
        closeBox.setAttribute('onclick', 'removeAnnotation("' + data.anchorName + '")');
      }
      annotation.appendChild(closeBox);
      
      var heading = document.createElement('div');
      heading.setAttribute('class', 'heading');
      
      var testLink = document.createElement('a');
      testLink.setAttribute('href', data.testURI);

      if (1 == data.testCount) {
        testLink.appendChild(document.createTextNode('1 Test'));
      }
      else {
        testLink.appendChild(document.createTextNode(data.testCount + ' Tests'));
      }
      if ((! annotationsClosed) && (0 < data.needData)) {
        var image = document.createElement('img');
        image.setAttribute('src', NEED_TEST_URI);
        image.setAttribute('class', 'need');
        testLink.appendChild(image);

        if (1 == data.needData) {
          testLink.setAttribute('title', '1 test needs results from your client, please click here to run test');
        }
        else {
          testLink.setAttribute('title', data.needData + ' tests need results from your client, please click here to run tests');
        }
        var untested = document.createElement('span');
        untested.appendChild(document.createTextNode(' ' + data.needData + '\u00A0untested, please\u00A0test'));
        testLink.appendChild(untested);
      }
      heading.appendChild(testLink);
      annotation.appendChild(heading);

      if (! annotationsClosed) {
        var engines = document.createElement('div');
        engines.setAttribute('class', 'engines');
        
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
                if ((resultCount / data.testCount) < 0.95) {
                  engineClass = 'uncertain';
                }
                else {
                  engineClass = 'p' + Math.round((engineData.passCount / data.testCount) * 10.0) + '0';
                }
              }
            }
          }
          else {
            toolTip = 'No data';
          }
          
          if (0 < resultCount) {
            engineNode.setAttribute('title', toolTip);
            engineNode.setAttribute('class', engineClass);

            if (0 < resultCount) {
              var detailsLink = document.createElement('a');
              detailsLink.setAttribute('href', engineData.detailsURI);
              
              detailsLink.appendChild(document.createTextNode(engineData.title));
              engineNode.appendChild(detailsLink);
            }
            else {
              engineNode.appendChild(document.createTextNode(engineData.title));
            }
            
            engines.appendChild(engineNode);
            engines.appendChild(document.createTextNode(' '));
          }
        }
        annotation.appendChild(engines);
      }
      
      element.parentNode.insertBefore(annotation, element);
    }
  }
  catch (err) {
//    document.body.innerHTML = 'EXCEPTION: ' + err.toString(); // DEBUG
  }
}


function addAnnotation(testData, first)
{
  try {
    var headings = {'h1':'', 'h2':'', 'h3':'', 'h4':'', 'h5':'', 'h6':'',
                    'H1':'', 'H2':'', 'H3':'', 'H4':'', 'H5':'', 'H6':''};
    var anchorName = testData.anchorName;

    if (anchorName) { // find heading that contains anchor
      var anchors = document.getElementsByName(anchorName);
      
      for (index in anchors) {
        var anchor = anchors[index];
        var heading = anchor.parentNode;
        
        while (heading && (Node.ELEMENT_NODE == heading.nodeType) && (! (heading.tagName in headings))) {
          heading = heading.parentNode;
        }
        if (heading && (Node.ELEMENT_NODE == heading.nodeType)) {
          addAnnotationTo(heading, testData, first);
          break;
        }
      }
    }
    else {  // find first h1
      var headings = document.getElementsByTagName('h1');
      
      if (headings && (0 < headings.length)) {
        addAnnotationTo(headings[0], testData, first);
      }
    }
  }
  catch (err) {
  }
}


function addAnnotations()
{
  try {
    if (annotationData) {
      if (annotationData instanceof Array) {
        if (0 < annotationData.length) {
          if (annotationsClosed) {
            addAnnotation(annotationData[0], true);
          }
          else {
            var first = true;
            for (index in annotationData) {
              addAnnotation(annotationData[index], first);
              first = false;
            }
          }
        }
      }
      else {
        addAnnotation(annotationData, true);
      }
    }
  }
  catch (err) {
  }
}


function processResponse(contentType, responseText)
{
  try {
    if (-1 < contentType.indexOf('application/json')) {
      var data = JSON.parse(responseText);
      
      if (data) {
        annotationData = data;
        addAnnotations();
      }
    }
  }
  catch (err) {
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
        if (-1 < scriptSource.indexOf('/annotate.js#')) {
          testSuiteName = scriptSource.substr(scriptSource.indexOf('#') + 1);
          if ('!' == testSuiteName[0]) {
            testSuiteName = testSuiteName.substr(1);
            annotationsClosed = true;
          }
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

      var statusURI = QUERY_URI + '?s=' + encodeURIComponent(testSuiteName) + '&x=' + encodeURIComponent(document.URL);
      
      if (window.XDomainRequest) {  // The IE way...
        var xdr = new XDomainRequest();
        if (xdr) {
          xdr.onload = function () {
            processResponse(xdr.contentType, xdr.responseText);
          }
          xdr.open('GET', statusURI);
          xdr.send();
        }
      }
      else {  // The standard way
        var xhr = new XMLHttpRequest();
        
        xhr.onreadystatechange = function() {
          if (4 == xhr.readyState) {
            if (200 == xhr.status) {
              processResponse(xhr.getResponseHeader('Content-Type'), xhr.responseText);
            }
            else if (500 == xhr.status) {
//              document.documentElement.innerHTML = xhr.responseText;  // DEBUG
            }
            else {
//              document.body.innerHTML = 'error: ' + xhr.status; // DEBUG
            }
          }
        };
        
        xhr.open('GET', statusURI, true);
        xhr.setRequestHeader('Accept', 'application/json,text/html');
        xhr.send();
      }
    }
  }
  catch (err) {
//    document.body.innerHTML = 'EXCEPTION: ' + err.toString(); // DEBUG
  }
}


function addLoadEvent(loadFunc)
{
  try {
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
  catch (err) {
  }
}


addLoadEvent(annotate);

