<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 10/12/2018
 * Time: 18:31
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\OptionsBox\Boxes\File;

use WPCCrawler\Objects\Enums\FileTemplateShortCodeName;
use WPCCrawler\Objects\Enums\InformationMessage;
use WPCCrawler\Objects\Enums\InformationType;
use WPCCrawler\Objects\File\FileService;
use WPCCrawler\Objects\File\MediaFile;
use WPCCrawler\Objects\Informing\Information;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\OptionsBox\Boxes\Base\BaseOptionsBoxApplier;
use WPCCrawler\Objects\ShortCode\ShortCodeApplier;
use WPCCrawler\Objects\ShortCode\ShortCodeButton;
use WPCCrawler\Objects\Traits\FindAndReplaceTrait;
use WPCCrawler\Test\TestService;

class FileOptionsBoxApplier extends BaseOptionsBoxApplier {

    use FindAndReplaceTrait;

    /** @var bool */
    private $applyFindReplaceOptions = true;

    /** @var bool */
    private $applyTemplateOptions = true;

    /** @var bool */
    private $applyFileOperationsOptions = true;

    /**
     * Applies the options configured in options box to the given value
     * @param mixed|MediaFile $value
     * @return mixed|null $modifiedValue Null, if the item should be removed. Otherwise, the modified value.
     */
    protected function onApply($value) {
        // If this is for a test applied outside the options box but in the site settings page and there is a valid
        // value, assume that it is a valid URL and create a temporary media file for that URL.
        if ($this->isForTest() && !($value instanceof MediaFile) && $value) {
            $value = TestService::getInstance()->createTempMediaFileForUrl($value);
        }

        // The value must be an instance of MediaFile
        if (!($value instanceof MediaFile)) return $value;

        // If the local path does not exist, stop.
        if (!$value->exists()) {
            Informer::add(Information::fromInformationMessage(
                InformationMessage::FILE_NOT_EXIST,
                $value->getLocalPath() ?: '',
                InformationType::ERROR
            )->addAsLog());

            return $value;
        }

        // If the file does not have an extension, stop.
        if (!$value->isFile()) return $value;

        // Apply the options
        $this->applyFindReplaceOptions($value);
        $this->applyFileOperationOptions($value);
        $this->applyTemplateOptions($value);

        // If this was for a test conducted outside of the options box
        if ($this->isForTest() && !$this->isFromOptionsBox()) {
            // Delete the files of the temporary media file.
            $value->deleteCopyFiles();
            $value->delete();

            // Return a single result so that the test results view can show it. Otherwise, it cannot handle objects.
            // This is basically intended for tests that are outside of the options box, the tests done by clicking the
            // test button in the site settings page.
            return $value->getLocalUrl();
        }

        return $value;
    }

    public function shouldApply($value): bool {
        // Apply only if the options box has options.
        return $this->isApplyFindReplaceOptions() ||
            $this->isApplyFileOperationsOptions() ||
            $this->isApplyTemplateOptions();
    }

    /*
     * APPLIER CONFIGURATION METHODS
     */

    /**
     * @param bool $apply
     * @return FileOptionsBoxApplier
     * @noinspection PhpUnused
     */
    public function setApplyFindReplaceOptions(bool $apply): self {
        $this->applyFindReplaceOptions = $apply;
        return $this;
    }

    /**
     * @return bool True if the find-replace options exist, and they should be applied.
     * @since 1.11.0
     */
    public function isApplyFindReplaceOptions(): bool {
        return $this->applyFindReplaceOptions && $this->getData()->hasFindReplaceOptions();
    }

    /**
     * @param bool $apply
     * @return FileOptionsBoxApplier
     */
    public function setApplyTemplateOptions(bool $apply): self {
        $this->applyTemplateOptions = $apply;
        return $this;
    }

    /**
     * @return bool True if the template options exist, and they should be applied.
     * @since 1.11.0
     */
    public function isApplyTemplateOptions(): bool {
        return $this->applyTemplateOptions && $this->getData()->hasTemplateOptions();
    }

    /**
     * @param bool $apply
     * @return FileOptionsBoxApplier
     */
    public function setApplyFileOperationsOptions(bool $apply): self {
        $this->applyFileOperationsOptions = $apply;
        return $this;
    }

    /**
     * @return bool True if the file operation options exist, and they should be applied
     * @since 1.11.0
     */
    public function isApplyFileOperationsOptions(): bool {
        return $this->applyFileOperationsOptions && $this->getData()->hasFileOperationOptions();
    }

    /*
     * APPLIER METHODS
     */

    /**
     * Applies find-replace options.
     *
     * @param MediaFile $mediaFile
     * @since 1.8.0
     */
    private function applyFindReplaceOptions($mediaFile): void {
        if (!$this->isApplyFindReplaceOptions()) return;

        // Get find-replace options
        $frOptions = $this->getData()->getFindReplaceOptions();
        if (!$frOptions) return;

        $name = $mediaFile->getName();
        if ($name === null) return;

        // Apply find-replace options to the name
        $newName = $this->findAndReplace($frOptions, $name);

        // Rename the file
        $mediaFile->rename($newName);
    }

    /**
     * Applies template options.
     *
     * @param MediaFile $mediaFile
     * @since 1.8.0
     */
    private function applyTemplateOptions(MediaFile $mediaFile): void {
        if (!$this->isApplyTemplateOptions()) return;

        $templateOptions = $this->getData()->getTemplateOptions();
        if (!$templateOptions) return;

        // Create a short code applier that applies the map storing short code names and corresponding values
        $shortCodeApplier = $this->createShortCodeApplier($mediaFile);

        // File name templates
        $prevFilePath = $mediaFile->getLocalPath();
        $this->applyTemplateWithCallback($templateOptions->getFileNameTemplates(), $shortCodeApplier, function($v) use (&$mediaFile) {
            $mediaFile->rename($v);
        });

        // Get a fresh map if the file path has changed.
        if ($prevFilePath !== $mediaFile->getLocalPath()) {
            $shortCodeApplier = $this->createShortCodeApplier($mediaFile);
        }

        // Media title templates
        $this->applyTemplateWithCallback($templateOptions->getMediaTitleTemplates(), $shortCodeApplier, function($v) use (&$mediaFile) {
            $mediaFile->setMediaTitle($v);
        });

        // Media description templates
        $this->applyTemplateWithCallback($templateOptions->getMediaDescriptionTemplates(), $shortCodeApplier, function($v) use (&$mediaFile) {
            $mediaFile->setMediaDescription($v);
        });

        // Media caption templates
        $this->applyTemplateWithCallback($templateOptions->getMediaCaptionTemplates(), $shortCodeApplier, function($v) use (&$mediaFile) {
            $mediaFile->setMediaCaption($v);
        });

        // Media alt templates
        $this->applyTemplateWithCallback($templateOptions->getMediaAltTemplates(), $shortCodeApplier, function($v) use (&$mediaFile) {
            $mediaFile->setMediaAlt($v);
        });
    }

    /**
     * @param MediaFile $mediaFile Media file for which a short code applier will be created
     * @return ShortCodeApplier A short code applier that applies the short code map of the media file
     * @since 1.13.0
     */
    private function createShortCodeApplier(MediaFile $mediaFile): ShortCodeApplier {
        return (new ShortCodeApplier($mediaFile->getShortCodeMap()))
            ->setCustomAppliersDisabled(true)
            // Inject the short code values into the generative short code templates, because the short code values are
            // not available anywhere else. Those values are specific to the media file. So, this is the only place
            // where those short code values can be injected.
            ->setInjectValuesToCustomAppliers(true);
    }

    /**
     * Applies file operations options.
     *
     * @param MediaFile $mediaFile
     * @since 1.8.0
     */
    private function applyFileOperationOptions(MediaFile $mediaFile): void {
        if (!$this->isApplyFileOperationsOptions()) return;

        $options = $this->getData()->getFileOperationOptions();
        if (!$options) return;

        // Move the file
        $movePaths = $options->getMovePaths();
        if ($movePaths) $this->move($mediaFile, $movePaths[array_rand($movePaths, 1)]);

        // Copy the file
        $copyPaths = $options->getCopyPaths();
        if ($copyPaths) $this->copy($mediaFile, $copyPaths);
    }

    /*
     *
     */

    /**
     * Apply templates with a callback
     *
     * @param string|string[]|null $templates        See {@link applyTemplate()}
     * @param ShortCodeApplier     $shortCodeApplier See {@link applyTemplate()}
     * @param callable             $setValueCallback A callback that takes only one parameter which is the prepared
     *                                               template. Returns nothing. E.g. function($value) {}
     * @since 1.8.0
     */
    private function applyTemplateWithCallback($templates, ShortCodeApplier $shortCodeApplier, $setValueCallback): void {
        if (!$templates) return;

        $value = trim($this->applyTemplate($templates, $shortCodeApplier));
        if (!$value) return;

        call_user_func($setValueCallback, $value);
    }

    /**
     * Apply templates.
     *
     * @param string|string[]  $templates        A template or an array of templates. In case of array, a random
     *                                           template will be selected.
     * @param ShortCodeApplier $shortCodeApplier A short code applier that will be used to replace the values of the
     *                                           short codes in the given template
     * @return string A template whose short codes are replaced
     * @since 1.8.0
     */
    private function applyTemplate($templates, ShortCodeApplier $shortCodeApplier): string {
        if (!$templates) return '';
        $template = is_array($templates)
            ? $templates[array_rand($templates, 1)]
            : $templates;
        return $shortCodeApplier->apply($template);
    }

    /**
     * Move the media file.
     *
     * @param MediaFile   $mediaFile
     * @param string|null $relativeDirectoryPath The directory path to which the file should be moved. The path should
     *                                           be relative to WordPress' uploads directory.
     * @since 1.8.0
     */
    private function move(MediaFile $mediaFile, ?string $relativeDirectoryPath): void {
        if (!$relativeDirectoryPath) return;

        // Get new directory path by making sure it does not go above the uploads directory.
        $newDirectoryPath = FileService::getInstance()->getPathUnderUploadsDir($relativeDirectoryPath);
        if (!$newDirectoryPath) return;

        // Move the file
        $mediaFile->moveToDirectory($newDirectoryPath);
    }

    /**
     * Copy the media file.
     *
     * @param MediaFile       $mediaFile
     * @param string|string[] $directories Directory or directories to which the file should be copied.
     * @since 1.8.0
     */
    private function copy($mediaFile, $directories): void {
        if (!$directories) return;

        if (!is_array($directories)) $directories = [$directories];

        foreach($directories as $relativeDirectoryPath) {
            // Get new directory path by making sure it does not go above the uploads directory.
            $newDirectoryPath = FileService::getInstance()->getPathUnderUploadsDir($relativeDirectoryPath);
            if (!$newDirectoryPath) continue;

            // Copy the file.
            $mediaFile->copyToDirectory($newDirectoryPath);
        }

    }

    /*
     *
     */

    /**
     * @return FileOptionsBoxData
     * @since 1.8.0
     */
    public function getData(): FileOptionsBoxData {
        $data = parent::getData();
        return $data instanceof FileOptionsBoxData
            ? $data
            : new FileOptionsBoxData([]);
    }

    /*
     * STATIC METHODS
     */

    /**
     * Get short code buttons that can be shown in the templates tab of file options box.
     *
     * @return ShortCodeButton[]
     * @since 1.8.0
     */
    public static function getShortCodeButtons(): array {
        return [
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::ORIGINAL_FILE_NAME,  _wpcc('Original file name (without extension)')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::ORIGINAL_TITLE,      _wpcc('Original title of the file')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::ORIGINAL_ALT,        _wpcc('Original alt text of the file')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::PREPARED_FILE_NAME,  _wpcc('File name prepared by find-replace options (without extension)')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::FILE_EXT,            _wpcc('File extension without a dot')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::MIME_TYPE,           _wpcc('Mime type')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::FILE_SIZE_BYTE,      _wpcc('File size in bytes')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::FILE_SIZE_KB,        _wpcc('File size in kilobytes')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::FILE_SIZE_MB,        _wpcc('File size in megabytes')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::BASE_NAME,           _wpcc('Prepared file name with its extension')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::MD5_HASH,            _wpcc('MD5 hash of the file')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::RANDOM_HASH,         _wpcc('Random SHA1 hash created by using the name of the file')),
            ShortCodeButton::getShortCodeButton(FileTemplateShortCodeName::LOCAL_URL,           _wpcc('Local URL of the file')),
        ];
    }
}