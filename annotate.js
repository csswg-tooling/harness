<?php 

require_once('lib/HarnessPage.php'); 

// manually generate proper content type header, because using the PHP processor confuses Apache
header('Content-Type: application/javascript; charset=utf-8');

?>
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
  
  response.info;
  response.engines[];
  response.sections[];

  info.annotationTitle;
  info.testSuiteTitle;
  info.testSuiteDescription;
  info.testSuiteDate;
  info.testSuiteLocked;
  info.testURI;
  info.resultsURI;
  info.detailsURI;
  info.clientEngineName;
  info.isIndexPage;
  
  section.anchorName;
  section.section;
  section.testCount;
  section.engines[];

  engineInfo.title;       // human readable title
  engineInfo.name;        // string key for harness
  
  engine.index;
  engine.passCount;
  engine.failCount;

**/

var annotator = {
  QUERY_URI:          "<?php echo HarnessPage::ExternalPageURI('status_query'); ?>",
  STYLESHEET_URI:     "<?php echo HarnessPage::ExternalConfigURI('stylesheet.annotation'); ?>",
  NEED_TEST_ICON_URI: "<?php echo HarnessPage::ExternalConfigURI('image.please_help'); ?>",
  ENGINE_LOGOS: { 'gecko': "<?php  echo HarnessPage::ExternalConfigURI('image.gecko'); ?>",
                  'presto': "<?php  echo HarnessPage::ExternalConfigURI('image.presto'); ?>",
                  'trident': "<?php  echo HarnessPage::ExternalConfigURI('image.trident'); ?>",
                  'webkit': "<?php  echo HarnessPage::ExternalConfigURI('image.webkit'); ?>" },

  mResponse: null,
  mClosed: false,
  
  buildURI: function(base, section) {
    if (section) {
      return base + 'section/' + section + '/';
    }
    return base;
  },
  
  removeAnnotation: function(anchorName) {
    try {
      var annotation = document.getElementById('annotation_' + ((0 < anchorName.length) ? anchorName : 'root_'));

      if (annotation) {
        annotation.parentNode.removeChild(annotation);
      }
    }
    catch (err) {
    }
  },
  
  removeAllAnnotations: function () {
    try {
      if (this.mResponse && this.mResponse.sections) {
        for (index in this.mResponse.sections) {
          this.removeAnnotation(this.mResponse.sections[index].anchorName);
        }
      }
    }
    catch (err) {
    }
  },
  
  toggleAnnotations: function() {
    this.mClosed = (! this.mClosed);
    this.removeAllAnnotations();
    this.addAnnotations();
  },
  
  toggleDetails: function(domEvent) {
    var engineNode = domEvent.target;
    while (engineNode && ('SPAN' != engineNode.tagName.toUpperCase())) {
      engineNode = engineNode.parentNode;
    }
    var engineIndex = engineNode.getAttribute('data-engineIndex');
    var annotation = engineNode.parentNode.parentNode;
    var details = annotation.lastChild;
    
    if (engineIndex == details.getAttribute('data-engineIndex')) {
      details.setAttribute('class', 'details');
      details.removeAttribute('data-engineIndex');
    }
    else {
      details.setAttribute('data-engineIndex', engineIndex);
      
      var engine = this.mResponse.engines[engineIndex];
      var section = annotation.getAttribute('data-section');

      var detailsEngine = details.firstChild;
      var detailsLink = detailsEngine.lastChild;
     
      detailsEngine.firstChild.textContent = engine.title + ' ';
      detailsLink.setAttribute('href', this.buildURI(this.mResponse.info.resultsURI, section));
      
      var meter = details.lastChild;
      var numbers = meter.firstChild;
      var passBar = numbers.nextSibling;
      var failBar = passBar.nextSibling;
      var needBar = failBar.nextSibling;
      numbers.textContent = engineNode.getAttribute('title');
      
      var passCount = parseInt(engineNode.getAttribute('data-passCount'), 10);
      var failCount = parseInt(engineNode.getAttribute('data-failCount'), 10);
      var needCount = parseInt(engineNode.getAttribute('data-needCount'), 10);
      var total = passCount + failCount + needCount;

      passBar.setAttribute('style', 'width: ' + ((passCount / total) * 100.0) + '%');
      failBar.setAttribute('style', 'width: ' + ((failCount / total) * 100.0) + '%');
      needBar.setAttribute('style', 'width: ' + ((needCount / total) * 100.0) + '%');
      
      details.setAttribute('class', 'details open');
    }
    
  },
  
  addAnnotationTo: function(anchorElement, section, first) {
    try {
      var headings = {'h1':'', 'h2':'', 'h3':'', 'h4':'', 'h5':'', 'h6':'',
                      'H1':'', 'H2':'', 'H3':'', 'H4':'', 'H5':'', 'H6':''};
      var targetElement = anchorElement;
      
      while (targetElement && (Node.ELEMENT_NODE == targetElement.nodeType) && (! (targetElement.tagName in headings))) {
        targetElement = targetElement.parentNode;
      }
      if (targetElement && (Node.ELEMENT_NODE == targetElement.nodeType)) {
        var needCount = section.testCount;
        for (index in section.engines) {
          var engine = section.engines[index];
          if (this.mResponse.engines[engine.index].name == this.mResponse.info.clientEngineName) {
            needCount = section.testCount - (engine.passCount + engine.failCount);
            break;
          }
        }

        var annotation = document.createElement('div');
        annotation.setAttribute('id', 'annotation_' + ((0 == section.anchorName.length) ? 'root_' : section.anchorName));
        var annotationClass = 'annotation';
        if (first) {
          annotationClass += ' first';
        }
        if (0 < needCount) {
          annotationClass += ' need';
        }
        if (this.mClosed) {
          annotationClass += ' closed';
        }
        annotation.setAttribute('class', annotationClass);
        if (section.section) {
          annotation.setAttribute('data-section', section.section);
        }
        annotation.setAttribute('data-testCount', section.testCount);
        annotation.setAttribute('data-needCount', needCount);

        // disclosure control
        if (first) {
          var disclosure = document.createElement('div');
          disclosure.setAttribute('class', 'disclosure button');
          disclosure.setAttribute('onclick', 'annotator.toggleAnnotations()');
          annotation.appendChild(disclosure);
        }
        
        // close box
        var closeBox = document.createElement('div');
        closeBox.setAttribute('class', 'close button');
        if (first) {
          closeBox.setAttribute('onclick', 'annotator.removeAllAnnotations()');
        }
        else {
          closeBox.setAttribute('onclick', 'annotator.removeAnnotation("' + section.anchorName + '")');
        }
        annotation.appendChild(closeBox);
        
        // Test suite info
        if ((! this.mClosed) && first && this.mResponse.info.isIndexPage && this.mResponse.info.annotationTitle) {
          var title = document.createElement('div');
          title.setAttribute('class', 'title');
          
          title.innerHTML = this.mResponse.info.annotationTitle;
          
          annotation.appendChild(title);
        }
        
        // Test count heading
        var heading = document.createElement('div');
        heading.setAttribute('class', 'heading');
        
        var testLink = document.createElement('a');
        testLink.setAttribute('href', this.buildURI(this.mResponse.info.testURI, section.section));

        if (1 == section.testCount) {
          testLink.appendChild(document.createTextNode('1 Test'));
        }
        else {
          testLink.appendChild(document.createTextNode(section.testCount + ' Tests'));
        }
        
        // Testing needed text
        if ((! this.mClosed) && (! this.mResponse.info.testSuiteLocked) && (0 < needCount)) {
          var untested = document.createElement('span');
          var image = document.createElement('img');
          image.setAttribute('src', this.NEED_TEST_ICON_URI);
          image.setAttribute('class', 'need');
          untested.appendChild(image);

          if (1 == needCount) {
            testLink.setAttribute('title', '1 test needs results from your client, please click here to run test');
          }
          else {
            testLink.setAttribute('title', needCount + ' tests need results from your client, please click here to run tests');
          }
          untested.appendChild(document.createTextNode(' ' + needCount + '\u00A0untested, please\u00A0test'));
          testLink.appendChild(untested);
        }
        heading.appendChild(testLink);
        annotation.appendChild(heading);

        // Engine result data
        if (! this.mClosed) {
          var majorEngines = document.createElement('div');
          var minorEngines = document.createElement('div');
          majorEngines.setAttribute('class', 'engines');
          minorEngines.setAttribute('class', 'engines');
          
          for (index in section.engines) {
            var engine = section.engines[index];
            var resultCount = (engine.passCount + engine.failCount);
            
            var toolTip = '';
            var engineClass = '';
            if (0 < resultCount) {
              if (engine.passCount == section.testCount) {
                toolTip = 'All tests pass';
                engineClass = 'pass';
              }
              else {
                if (engine.failCount == section.testCount) {
                  toolTip = 'All tests fail';
                  engineClass = 'epic-fail';
                }
                else {
                  if (0 < engine.passCount) {
                    toolTip = engine.passCount + ' pass';
                  }
                  if (0 < engine.failCount) {
                    if (toolTip.length) {
                      toolTip += ', '
                    }
                    toolTip += engine.failCount + ' fail';
                  }
                  if (resultCount < section.testCount) {
                    if (toolTip.length) {
                      toolTip += ', '
                    }
                    toolTip += (section.testCount - resultCount) + ' untested';
                  }
                  if ((resultCount / section.testCount) < 0.90) {
                    engineClass = 'untested';
                  }
                  else {
                    switch (Math.round((engine.passCount / section.testCount) * 10.0)) {
                      case 10:
                      case 9: engineClass = 'almost-pass';  break;
                      case 8: engineClass = 'slightly-buggy'; break;
                      case 7: engineClass = 'buggy'; break;
                      case 6: engineClass = 'very-buggy'; break;
                      case 5: engineClass = 'fail'; break;
                      default: engineClass = 'epic-fail'; break;
                    }
                  }
                }
              }
            }
            else {
              toolTip = 'No data';
            }
            
            if (0 < resultCount) {
              var engineNode = document.createElement('span');
              engineNode.setAttribute('title', toolTip);
              if (this.mResponse.engines[engine.index].name == this.mResponse.info.clientEngineName) {
                engineClass += ' active';
              }
              engineNode.setAttribute('tabindex', '0');
              engineNode.setAttribute('data-engineIndex', engine.index);
              engineNode.setAttribute('data-passCount', engine.passCount);
              engineNode.setAttribute('data-failCount', engine.failCount);
              engineNode.setAttribute('data-needCount', section.testCount - resultCount);
              engineNode.onclick = function(domEvent) { annotator.toggleDetails(domEvent); };

              if (this.mResponse.engines[engine.index].name in this.ENGINE_LOGOS) {
                engineClass += ' major';
                var logo = document.createElement('img');
                logo.setAttribute('src', this.ENGINE_LOGOS[this.mResponse.engines[engine.index].name]);
                engineNode.appendChild(logo);
                majorEngines.appendChild(engineNode);
                majorEngines.appendChild(document.createTextNode(' '));
              }
              else {
                engineNode.appendChild(document.createTextNode(this.mResponse.engines[engine.index].title));
                minorEngines.appendChild(engineNode);
                minorEngines.appendChild(document.createTextNode(' '));
              }

              engineNode.setAttribute('class', this.mResponse.engines[engine.index].name + ' ' + engineClass);
            }
          }
          annotation.appendChild(majorEngines);
          annotation.appendChild(minorEngines);
          
          var details = document.createElement('div');
          details.setAttribute('class', 'details');
          
          var engineName = document.createElement('div');
          engineName.setAttribute('class', 'engine');
          engineName.appendChild(document.createTextNode('engine '));
          
          var detailsLink = document.createElement('a');
          detailsLink.appendChild(document.createTextNode('test details'));
          engineName.appendChild(detailsLink);
          details.appendChild(engineName);
          
          var meter = document.createElement('div');
          meter.setAttribute('class', 'meter');
          for (barClass in { 'numbers': '', 'pass': '', 'epic-fail': '', 'untested': '' }) {
            var bar = document.createElement('span');
            bar.setAttribute('class', barClass);
            meter.appendChild(bar);
          }
          details.appendChild(meter);
          
          annotation.appendChild(details);
        }
        
        targetElement.insertBefore(annotation, targetElement.firstChild);
        return true;
      }
    }
    catch (err) {
//      document.body.innerHTML = 'EXCEPTION: ' + err.toString(); // DEBUG
    }
    return false;
  },
  
  addAnnotation: function(section, first) {
    try {
      var anchorName = section.anchorName;

      if (anchorName) { // find element that has anchor name or id
        var found = false;

        anchor = document.getElementById(anchorName);
        if (! (anchor && this.addAnnotationTo(anchor, section, first))) {
          var anchors = document.getElementsByName(anchorName);
          
          for (index in anchors) {
            var anchor = anchors[index];
            if (this.addAnnotationTo(anchor, section, first)) {
              break;
            }
          }
        }
      }
      else if (first) {  // find first h1
        var headings = document.getElementsByTagName('h1');
        
        if (headings && (0 < headings.length)) {
          this.addAnnotationTo(headings[0], section, first);
        }
      }
    }
    catch (err) {
    }
  },
  
  addAnnotations: function () {
    try {
      if (this.mResponse && this.mResponse.sections) {
        if (0 < this.mResponse.sections.length) {
          if (this.mClosed) {
            this.addAnnotation(this.mResponse.sections[0], true);
          }
          else {
            var first = true;
            for (index in this.mResponse.sections) {
              this.addAnnotation(this.mResponse.sections[index], first);
              first = false;
            }
          }
        }
      }
    }
    catch (err) {
    }
  },
  
  processResponse: function(contentType, responseText) {
    try {
      if (-1 < contentType.indexOf('application/json')) {
        var response = JSON.parse(responseText);
        
        if (response) {
          this.mResponse = response;
          this.addAnnotations();
        }
      }
    }
    catch (err) {
    }
  },
  
  annotate: function() {
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
              this.mClosed = true;
            }
            break;
          }
        }
      }
      
      if (0 < testSuiteName.length) {
        var styleSheet = document.createElement('link');
        styleSheet.setAttribute('rel', 'stylesheet');
        styleSheet.setAttribute('type', 'text/css');
        styleSheet.setAttribute('href', this.STYLESHEET_URI);
        document.getElementsByTagName('head')[0].appendChild(styleSheet)

        var statusURI = this.QUERY_URI + '?s=' + encodeURIComponent(testSuiteName) + '&x=' + encodeURIComponent(document.URL);
        if (window.XDomainRequest) {  // The IE way...
          var xdr = new XDomainRequest();
          if (xdr) {
            xdr.onload = function () {
              annotator.processResponse(xdr.contentType, xdr.responseText);
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
                annotator.processResponse(xhr.getResponseHeader('Content-Type'), xhr.responseText);
              }
              else if (500 == xhr.status) {
//                document.documentElement.innerHTML = xhr.responseText;  // DEBUG
              }
              else {
//                document.body.innerHTML = 'error: ' + xhr.status + xhr.responseText; // DEBUG
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
//      document.body.innerHTML = 'EXCEPTION: ' + err.toString(); // DEBUG
    }
  },
  
  addLoadEvent: function() {
    try {
      var oldOnLoad = window.onload;
      if (typeof window.onload != 'function') {
        window.onload = this.annotate();
      }
      else {
        window.onload = function () {
          if (oldOnLoad) {
            oldOnLoad();
          }
          annotator.annotate();
        }
      }
    }
    catch (err) {
    }
  }
}


annotator.addLoadEvent();

