<?php 
sendManualHeaders('ISO-8859-1','en');
setupNavigation(array(
  'home' => array('index.php', 'PEAR Manual'),
  'prev' => array('contributing-introducing.php', 'Introducing your code'),
  'next' => array('index.php#AEN0', ''),
  'up'   => array('contributing.php', 'Contributing to PEAR'),
  'toc'  => array(
    array('contributing.php#contributing', ''),
    array('contributing.php#AEN0', ''),
    array('contributing-howto.php', 'How to contribute to PEAR'),
    array('contributing.php#AEN0', ''),
    array('packages.skeleton.php', ''))));
manualHeader('','packages.skeleton.php');
?><DIV
CLASS="ARTICLE"
><DIV
CLASS="TITLEPAGE"
><H1
CLASS="title"
><A
NAME="AEN591"
>Skeleton for creating PEAR docbook documentation</A
></H1
><HR></DIV
><P
>You can get the XML soure file for the skeleton
 <A
HREF="http://pear.php.net/downloads/skeleton.xml"
TARGET="_top"
>here</A
></P
><DIV
CLASS="section"
><H1
CLASS="section"
><A
NAME="packages.skeleton.classname"
>Classname</A
></H1
><P
>This is a paragraph, where you can put text in.</P
><P
></P
><UL
><LI
><P
>The first item in the itemized list</P
></LI
><LI
><P
>An itemized list is comparable with 
    &#62;ul&#60;&#62;li&#60;&#62;/li&#60;&#62;/ul&#60; in HTML
    </P
></LI
></UL
><P
><I
CLASS="emphasis"
>Emphase</I
> something, could transform in cursive
  or bold in other formats like PDF or HTML
  </P
><TABLE
BORDER="0"
BGCOLOR="#E0E0E0"
WIDTH="100%"
><TR
><TD
><PRE
CLASS="programlisting"
>&#13;// Often, you have to add programing examples.
// Everything between the CDATA-section is treaten "as is"
// including tabs(!)

go_ahead() ;
  </PRE
></TD
></TR
></TABLE
><P
>&#13;   <B
CLASS="function"
>This_is_a_functionname()</B
> and 
   <B
CLASS="classname"
>i_am_the_name_of_a_class</B
> or do you want
   to describe a <TT
CLASS="parameter"
><I
>parameter</I
></TT
>?
  </P
></DIV
></DIV
><?php manualFooter('','packages.skeleton.php');
?>