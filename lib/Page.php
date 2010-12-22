<?php
/*******************************************************************************
 *
 *  Copyright © 2008-2010 Hewlett-Packard Development Company, L.P. 
 *
 *  This work is distributed under the W3C¨ Software License [1] 
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
  

////////////////////////////////////////////////////////////////////////////////
//
// class static_page
//
// This base object encapsulates the writing of basic HTML for pages used by a
// test harness. Subclasses of this class will add functionality needed by
// specific pages.
//
////////////////////////////////////////////////////////////////////////////////

class Page
{
  ////////////////////////////////////////////////////////////////////////////
  //
  //  Instance variables.
  //
  ////////////////////////////////////////////////////////////////////////////
  protected $mShouldCache;
  protected $mPageBase;
  
  
  
  function __construct() 
  {
    $this->mShouldCache = FALSE;
    $this->mPageBase = '';
    

  }  
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  htmlify()
  //
  //  Escape HTML characters to prevent strings text from containing HTML 
  //  markup.
  //
  //  The translations performed are: 
  //
  //    '&' (ampersand) becomes '&amp;' 
  //    ''' (single quote) becomes '&#039;' 
  //    '<' (less than) becomes '&lt;' 
  //    '>' (greater than) becomes '&gt;' 
  //
  //  See also php documentation for htmlspecialchars.
  //
  ////////////////////////////////////////////////////////////////////////////
  static function Encode($string)
  {
    return htmlspecialchars($string, ENT_QUOTES);
  }
  
  static function Decode($string)
  {
    return html_entity_decode($string, ENT_QUOTES);
  }
  
  function getPageTitle()
  {
    return '';
  }
  
  function getContentTitle()
  {
    return $this->getPageTitle();
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  write()
  // 
  //  Member function to write web page
  //
  //  When the server receives the HTTP request it locates the appropriate
  //  document and returns it. If the appropriate document is a php script,
  //  then the script gets executed and the generated results are returned.
  //
  //  A valid HTTP response is required to have a particular form. 
  //  It must look like this: 
  //
  //    HTTP/[VER] [CODE] [TEXT]
  //    Field1: Value1
  //    Field2: Value2
  //
  //    ...Document content here...
  //
  //  Thus, to generate a valid HTML response to an HTTP request we need to
  //  write all appropriate HTTP header information followed by the HTML
  //  content.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write()
  {
    $this->write_http_headers();
    $this->write_html();
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_http_headers()
  // 
  //  Member function to write HTTP header information
  //
  //  The first line of a valid HTTP header shows the HTTP version used,
  //  followed by a three-digit number (the HTTP status code) and a reason
  //  phrase meant for humans. Usually the code is 200 (which basically means
  //  that all is well) and the phrase "OK". 
  //
  //  The first line is followed by some lines called the header, which
  //  contains information about the document. The header ends with a blank
  //  line, followed by the document content.
  //
  //  PHP handles sending appropriate header information in response to a
  //  well-formed HTTP request; however, additional header information may
  //  be appended to these defaults before the HTML content is generated.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_http_headers()
  {
    if (! $this->mShouldCache) {
      header("Cache-Control: max-age=0");
    }
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_html()
  // 
  //  Member function to write the <html> page
  //
  //  An HTML file is identified as an HTML file by having its contents
  //  enclosed in the <html> container tag -- in other words, there is an
  //  <html> start tag at the top of the file and an </html> end tag at the
  //  bottom.
  //
  //  The <html> tag can only contain two things: a <head> container tag, and
  //  a <body> container tag. 
  //
  //  The optional <head> section, placed before the <body> section, stores
  //  certain information about the document itself. For example, it might
  //  contain the <title> container tag, which says what to display in the 
  //  title of the browser window, above the menu bar (if you have a
  //  graphical browser). 
  //
  //  The <body> section stores all displayed text, images, hyperlinks, etc.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_html($indent = '')
  {
    echo $indent;
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">';
    echo "\n";
    
    echo $indent . '<html lang="en">' . "\n";
    $this->write_html_head($indent . '  ');
    $this->write_html_body($indent . '  ');
    echo $indent . '</html>' . "\n";
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_html_head()
  // 
  //  Member function to create <head> section of the HTML document
  //
  //  The head section can contain information about the document. The browser 
  //  does not display the "head information" to the user. The following tags 
  //  can be in the head section: <base>, <link>, <meta>, <script>, <style>,  
  //  and <title>
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_html_head($indent = '')
  {
    echo $indent . "<head>\n";
    $this->write_head_base($indent . '  ');
    $this->write_head_title($indent . '  ');
    $this->write_head_metas($indent . '  ');
    $this->write_head_style($indent . '  ');
    $this->write_head_links($indent . '  ');
    $this->write_head_script($indent . '  ');
    echo $indent . "</head>\n";
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_base()
  //
  //  The base element specifies a base URL for all the links in a page.
  //
  //  Note: The <base> tag must go inside the head element.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_base($indent = '')
  {
    //
    // If the member varable "mPageBase" is not empty then output the
    // base tag.
    //
    if ($this->mPageBase) {
      echo $indent . "<base href='{$this->mPageBase}'>\n";  // XXX htmlEncode!
    }
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_title()
  //
  //  The <title> element defines the title of the document.
  //
  //  Note: The <title> tag must go inside the head element.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_title($indent = '')
  {
    $title = $this->getPageTitle();
    
    if ($title) {
      $title = Page::Encode($title);
      echo $indent . "<title>{$title}</title>\n";
    }
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_metas()
  //
  //  The <meta> elements provide meta-information about a web page, such as
  //  descriptions and keywords for search engines and refresh rates.
  //
  //  In HTML the <meta> tag has no end tag.
  //
  //  Note: The <meta> tag always goes inside the head element.
  //
  //  Note: Metadata is always passed as name/value pairs.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_metas($indent = '')
  {
    echo $indent . '<meta http-equiv="Content-Type" ';
    echo 'content="text/html; charset=utf-8">' . "\n";
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_style()
  //
  //  The <style> tag defines a style in a document.
  //
  //  Note: The style element goes in the head section.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_style($indent = '')
  {  
  }
  

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_links()
  //
  //  This <link> elements define the relationship between two linked
  //  documents.
  //
  //  In HTML the <link> tag has no end tag.
  //
  //  Note: The link element is an empty element, it contains attributes only.
  //
  //  Note: The link element goes only in the head section, but it can appear
  //  any number of times.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_links($indent = '')
  {
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_head_script()
  //
  //  This <script> element provides script available to the document
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_head_script($indent = '')
  {
  }
  
  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_html_body()
  //
  //  Member function to write common <body> tags at the start of the page 
  //     content.
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_html_body($indent = '')
  {
    echo $indent . '<body>' . "\n";
    $this->write_body_header($indent . '  ');
    $this->write_body_content($indent . '  ');
    $this->write_body_footer($indent . '  ');
    echo $indent . '</body>' . "\n";
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  //  write_body_header()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_header($indent = '')
  {
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_content()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_content($indent = '')
  {
  }

  ////////////////////////////////////////////////////////////////////////////
  //
  // write_body_footer()
  //
  ////////////////////////////////////////////////////////////////////////////
  function write_body_footer($indent = '')
  {
  }
}
?>