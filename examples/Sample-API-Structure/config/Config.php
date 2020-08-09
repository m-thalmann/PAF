<?php
    class Config{
        private static $config = NULL;

        public static function load($file){
            self::$config = @json_decode(file_get_contents($file), TRUE);

            if(self::$config === NULL || self::$config === FALSE){
                throw new Exception('Config could not be loaded');
            }
        }

        /**
         * Gets a value from the config
         * 
         * @param string $path The path to the value; if nested use '.'
         * 
         * @return mixed|null The value or null if not found
         */
        public static function get($path){
            $curr = &self::$config;

            foreach(explode('.', $path) as $key){
                if(!isset($curr[$key])) return NULL;

                $curr = &$curr[$key];
            }

            return $curr;
        }
    }
?>