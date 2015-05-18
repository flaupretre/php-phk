<?php
//=============================================================================
//
// Copyright Francois Laupretre <phk@tekwire.net>
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//
//=============================================================================

//-- We must change the returned mime type (default is text/html)
//-- And we must do it before any output

header('Content-type: text/css');

//=============================================================================
?>

/* tabs styles, based on http://www.alistapart.com/articles/slidingdoors */

DIV.tabs
{
   float            : left;
   width            : 100%;
   background       : url('<?php echo PHK::subpath_url('/section/STATIC/tabs/bottom.gif'); ?>') repeat-x bottom;
   margin-bottom    : 0px
}

DIV.tabs UL
{
   margin           : 0px;
   padding-left     : 10px;
   list-style       : none;
}

DIV.tabs LI, DIV.tabs FORM
{
   display          : inline;
   margin           : 0px;
   padding          : 0px;
}

DIV.tabs FORM
{
   float            : right;
}

DIV.tabs A
{
   float            : left;
   background       : url('<?php echo PHK::subpath_url('/section/STATIC/tabs/right.gif'); ?>') no-repeat right top;
   border-bottom    : 1px solid #84B0C7;
/*   font-size        : x-small;*/
   font-weight      : bold;
   text-decoration  : none
}

DIV.tabs A:hover
{
   background-position: 100% -150px;
}

DIV.tabs A:link, DIV.tabs A:visited,
DIV.tabs A:active, DIV.tabs A:hover
{
       color: #1A419D;
}

DIV.tabs SPAN
{
   float            : left;
   display          : block;
   background       : url('<?php echo PHK::subpath_url('/section/STATIC/tabs/left.gif'); ?>') no-repeat left top;
   white-space      : nowrap; padding-left:9px; padding-right:9px; padding-top:5px; padding-bottom:5px
}

DIV.tabs INPUT
{
   float            : right;
   display          : inline;
   font-size        : 1em;
}

DIV.tabs TD
{
/*   font-size        : x-small;*/
   font-weight      : bold;
   text-decoration  : none;
}



/* Commented Backslash Hack hides rule from IE5-Mac \*/
DIV.tabs SPAN {float : none;}
/* End IE5-Mac hack */

DIV.tabs A:hover SPAN
{
   background-position: 0% -150px;
}

DIV.tabs LI#current A
{
   background-position: 100% -150px;
   border-width     : 0px;
}

DIV.tabs LI#current SPAN
{
   background-position: 0% -150px;
   padding-bottom   : 6px;
}

DIV.nav
{
   background       : none;
   border           : none;
   border-bottom    : 1px solid #84B0C7;
}
