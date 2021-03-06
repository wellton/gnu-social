<?php

if (!defined('GNUSOCIAL')) { exit(1); }

// FIXME: To support remote video/whatever files, this plugin needs reworking.

class StoreRemoteMediaPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    // settings which can be set in config.php with addPlugin('Embed', array('param'=>'value', ...));
    // WARNING, these are _regexps_ (slashes added later). Always escape your dots and end your strings
    public $domain_whitelist = [
        // hostname             => service provider
        '^i\d*\.ytimg\.com$'    => 'YouTube',
        '^i\d*\.vimeocdn\.com$' => 'Vimeo',
        ];

    public $append_whitelist = [];    // fill this array as domain_whitelist to add more trusted sources
    public $check_whitelist  = false; // security/abuse precaution

    public $domain_blacklist = [];
    public $check_blacklist = false;

    public $max_image_bytes = 10 * 1024 * 1024;  // 10MiB max image size by default

    protected $imgData = [];

    // these should be declared protected everywhere
    public function initialize()
    {
        parent::initialize();

        $this->domain_whitelist = array_merge($this->domain_whitelist, $this->append_whitelist);
    }

    public function onCreateFileImageThumbnailSource(File $file, &$imgPath, $media=null)
    {
        // If we are on a private node, we won't do any remote calls (just as a precaution until
        // we can configure this from config.php for the private nodes)
        if (common_config('site', 'private')) {
            return true;
        }

        if ($media !== 'image') {
            return true;
        }

        // If there is a local filename, it is either a local file already or has already been downloaded.
        if (!empty($file->filename)) {
            return true;
        }

        $remoteUrl = $file->getUrl();

        if (empty($remoteUrl)) {
            return true;
        }

        if (!$this->checkWhiteList($remoteUrl) ||
            !$this->checkBlackList($remoteUrl)) {
		    return true;
        }

        // Relative URL, something's off
        if (empty(parse_url($remoteUrl, PHP_URL_HOST))) {
            common_err("StoreRemoteMedia found a url without host (\"{$remoteUrl}\") for file with id = {$file->id}");
            return true;
        }

        try {

            $http = new HTTPClient();
            common_debug(sprintf('Performing HEAD request for remote file id==%u to avoid '.
                                 'unnecessarily downloading too large files. URL: %s',
                                 $file->getID(), $remoteUrl));

            $url = $remoteUrl;
            $head = $http->head($remoteUrl);
            $remoteUrl = $head->getEffectiveUrl();   // to avoid going through redirects again

            if (empty($remoteUrl)) {
                common_log(LOG_ERR, "URL after redirects is somehow empty, for URL {$url}");
                return true;
            }

            if (!$this->checkBlackList($remoteUrl)) {
                common_log(LOG_WARN, sprintf('%s: Non-blacklisted URL %s redirected to blacklisted URL %s',
                                             __CLASS__, $file->getUrl(), $remoteUrl));
                return true;
            }

            $headers = $head->getHeader();
            $headers = array_change_key_case($headers, CASE_LOWER);
            $filesize = isset($headers['content-length']) ?: $file->getSize();

            if (empty($filesize)) {
                // file size not specified on remote server
                common_debug(sprintf('%s: Ignoring remote media because we did not get a ' .
                                     'content length for file id==%u', __CLASS__, $file->getID()));
                return true;
            } elseif ($filesize > $this->max_image_bytes) {
                //FIXME: When we perhaps start fetching videos etc. we'll need to
                // differentiate max_image_bytes from that...

                // file too big according to plugin configuration
                common_debug(sprintf('%s: Skipping remote media because content length (%u) ' .
                                     'is larger than plugin configured max_image_bytes (%u) ' .
                                     'for file id==%u', __CLASS__, intval($filesize),
                                     $this->max_image_bytes, $file->getID()));
                return true;
            } elseif ($filesize > common_config('attachments', 'file_quota')) {
                // file too big according to site configuration
                common_debug(sprintf('%s: Skipping remote media because content length (%u) ' .
                                     'is larger than file_quota (%u) for file id==%u',
                                     __CLASS__, intval($filesize),
                                     common_config('attachments', 'file_quota'), $file->getID()));
                return true;
            }

            // Then we download the file to memory and test whether it's actually an image file
            common_debug(sprintf('Downloading remote file id=%u (should be size %u) ' .
                                 'with effective URL: %s', $file->getID(), $filesize, _ve($remoteUrl)));
            $imgData = HTTPClient::quickGet($remoteUrl);
        } catch (HTTP_Request2_ConnectionException $e) {
            common_log(LOG_ERR, __CLASS__.': '._ve(get_class($e)).' on URL: ' .
                       _ve($file->getUrl()).' threw exception: '.$e->getMessage());
            return true;
        }
        $info = @getimagesizefromstring($imgData);
        if ($info === false) {
            throw new UnsupportedMediaException(_('Remote file format was not identified as an image.'), $remoteUrl);
        } elseif (!$info[0] || !$info[1]) {
            throw new UnsupportedMediaException(_('Image file had impossible geometry (0 width or height)'));
        }

        $filehash = hash(File::FILEHASH_ALG, $imgData);
        try {
            // Exception will be thrown before $file is set to anything, so old $file value will be kept
            $file = File::getByHash($filehash);

            //FIXME: Add some code so we don't have to store duplicate File rows for same hash files.
        } catch (NoResultException $e) {
            $original_name = HTTPClient::get_filename($remoteUrl, $headers);
            $filename = MediaFile::encodeFilename($original_name, $filehash);
            $fullpath = File::path($filename);

            common_debug("StoreRemoteMedia retrieved url {$remoteUrl} for file with id={$file->id} " .
                         "and will store in {$fullpath}");

            // Write the file to disk if it doesn't exist yet. Throw Exception on failure.
            if ((!file_exists($fullpath) || substr($fullpath, 0, strlen(INSTALLDIR)) != INSTALLDIR) &&
                file_put_contents($fullpath, $imgData) === false) {
                throw new ServerException(_('Could not write downloaded file to disk.'));
            }

            // Updated our database for the file record
            $orig = clone($file);
            $file->filehash = $filehash;
            $file->filename = $filename;
            $file->width = $info[0];    // array indexes documented on php.net:
            $file->height = $info[1];   // https://php.net/manual/en/function.getimagesize.php
            // Throws exception on failure.
            $file->updateWithKeys($orig);
        }

        // Get rid of the file from memory
        unset($imgData);

        // Output
        $imgPath = $file->getPath();

        return false;
    }

    /**
     * @return boolean          true if given url passes blacklist check
     */
    protected function checkBlackList($url)
    {
        if (!$this->check_blacklist) {
            return true;
        }
        $host = parse_url($url, PHP_URL_HOST);
        foreach ($this->domain_blacklist as $regex => $provider) {
            if (preg_match("/$regex/", $host)) {
                return false;
            }
        }

        return true;
    }

    /***
     * @return boolean          true if given url passes whitelist check
     */
    protected function checkWhiteList($url)
    {
        if (!$this->check_whitelist) {
            return true;
        }
        $host = parse_url($url, PHP_URL_HOST);
        foreach ($this->domain_whitelist as $regex => $provider) {
            if (preg_match("/$regex/", $host)) {
                return true;
            }
        }

        return false;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'StoreRemoteMedia',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => GNUSOCIAL_ENGINE_URL,
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Plugin for downloading remotely attached files to local server.'));
        return true;
    }
}
