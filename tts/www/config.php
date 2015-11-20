<?php
#Active CDR statistics :
$enable_record_to_cdr=false;


#Common Languages en variantes
#http://www.w3.org/International/articles/language-tags/
#
# language-Script-REGION-variant-extension-privateuse
#
#The entries in the registry follow certain conventions with regard to upper and 
#lowercasing - for example, language tags are lower case, alphabetic region subtags
#are upper case, and script tags begin with an initial capital. This is only a 
#convention! When you use these subtags you are free to do as you like.


#default tts engine is the first in this tts array :
$tts=array();
#flite_begin
$tts["flite"]= array(
      "name" => "flite",
      "call"  => "/usr/bin/flite",
      "options" => array('-f $filename', '-o $filename.$iformat'),
      "voices"=>array(
                      'voice1'  => array(
                       'type'=>'male',
                       'desc'=>'English Male',
                       'voptions' => array ()
                      ),
             ),
      "langcodes" => array(
                         'en' => array('voice1'),
                       ),
      "charsets" => array('UTF-8'),
      "codecs" => array('pcm'=>'pcm'),
      "iformats" => array('wav'),
      "vxiformats" => array('wav' => array('iformat'=>'wav','codec'=>'pcm')),
      "formatiscodec"=>false,
      "debugargs"=>"-v",
      "checks" => array("/usr/bin/flite"),
      "simexeclimit" => "0",
);
#flite_end


# vim: set filetype=php expandtab tabstop=2 shiftwidth=2 autoindent smartindent:

