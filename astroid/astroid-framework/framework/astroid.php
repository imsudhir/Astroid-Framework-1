<?php
jimport('astroid.framework.template');

abstract class AstroidFramework {

   public static $template = null;
   public static $debug = true;
   public static $debugmarker = null;

   public static function getTemplate() {
      if (!self::$template) {
         self::$template = self::createTemplate();
      }

      return self::$template;
   }

   public static function createTemplate() {
      return new AstroidFrameworkTemplate(JFactory::getApplication()->getTemplate(true));
   }

   public static function debug($status = 'stop', $restart = false) {
      if (!self::$debug) {
         return;
      }
      if ($status == "start") {
         if (self::$debugmarker !== null) {
            self::debug('stop', true);
         } else {
            self::$debugmarker = getrusage();
         }
      } else {
         if (self::$debugmarker === null) {
            return;
         }
         $stop = getrusage();
         $utime = self::getRunTime($stop, self::$debugmarker, "utime");
         $stime = self::getRunTime($stop, self::$debugmarker, "stime");
         if (!isset($_COOKIE['_astroidDebuggerRecords'])) {
            $records = [];
         } else {
            $records = \json_decode($_COOKIE['_astroidDebuggerRecords'], true);
            if (empty($records)) {
               $records[] = ['utime' => $utime, 'stime' => $stime];
            }
         }
         self::saveDebug($utime, $stime, $records);
         ob_start();
         ?>
         <div class="d-flex p-2 justify-content-between" id="astroid-debuger" style="position: fixed;bottom: 0;left: 0;width: 100%;background: #000;color: #b7b7b7;z-index: 9999">
            <p class='text-white m-0'><label class='m-0'><input oninput='astroidDebuggerRecord(this.checked)' <?php echo (isset($_COOKIE['_astroidDebuggerRecord']) && $_COOKIE['_astroidDebuggerRecord'] == 1) ? 'checked="checked"' : ''; ?> type='checkbox' /> Record</label> <?php echo self::getRecordsCount($records); ?></p>
            <?php echo self::getRecords($records); ?>
            <p class='m-0'>Process used <strong class="text-white"><em><?php echo $utime; ?> ms</em></strong> and System calls spent <strong class="text-white"><em><?php echo $stime; ?> ms</em></strong></p>

         </div>
         <script>
            function astroidDebuggerRecord(_value) {
               if (_value) {
                  document.cookie = "_astroidDebuggerRecord=1";
               } else {
                  document.cookie = "_astroidDebuggerRecord=0";
               }
               document.cookie = "_astroidDebuggerRecords=[]";
            }
         </script>
         <?php
         $output = ob_get_contents();
         ob_end_clean();
         echo $output;
         self::$debugmarker = null;
         if ($restart) {
            self::debug('start');
         }
      }

      //document.cookie = "username=John Doe";
   }

   public static function saveDebug($utime, $stime, $records) {
      if (isset($_COOKIE['_astroidDebuggerRecord']) && $_COOKIE['_astroidDebuggerRecord'] == 1) {
         $records[] = ['utime' => $utime, 'stime' => $stime];
         setcookie('_astroidDebuggerRecords', \json_encode($records));
      } else {
         setcookie('_astroidDebuggerRecords', "[]");
      }
   }

   public static function getRecords($records) {
      if (isset($_COOKIE['_astroidDebuggerRecord']) && $_COOKIE['_astroidDebuggerRecord'] == 1) {
         if (!isset($_COOKIE['_astroidDebuggerRecords'])) {
            return "";
         }
         $utime = 0;
         $stime = 0;

         foreach ($records as $record) {
            $utime += $record['utime'];
            $stime += $record['stime'];
         }

         $utime = (int) ($utime / count($records));
         $stime = (int) ($stime / count($records));

         return "<p class='m-0'>Avg Processing time: <strong class='text-white'><em>" . $utime . " ms</em></strong>, Avg System calls time: <strong class='text-white'><em>" . $stime . " ms</em></strong></p>";
      }
   }

   public static function getRecordsCount($records) {
      if (isset($_COOKIE['_astroidDebuggerRecord']) && $_COOKIE['_astroidDebuggerRecord'] == 1) {
         if (!isset($_COOKIE['_astroidDebuggerRecords'])) {
            return "(0 calls)";
         }
         return "(" . count($records) . " call" . (count($records) == 1 ? '' : 's') . ")";
      }
   }

   public static function getRunTime($ru, $rus, $index) {
      return ($ru["ru_$index.tv_sec"] * 1000 + intval($ru["ru_$index.tv_usec"] / 1000)) - ($rus["ru_$index.tv_sec"] * 1000 + intval($rus["ru_$index.tv_usec"] / 1000));
   }

}
