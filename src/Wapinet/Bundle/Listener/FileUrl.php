<?php

namespace Wapinet\Bundle\Listener;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;

class FileUrl implements EventSubscriber
{
    /**
     * The events the listener is subscribed to.
     *
     * @return array The array of events.
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'postFlush',
            'preUpdate',
            'postRemove',
        );
    }

    /**
     * Checks for for file to upload and store it for store at postFlush event
     *
     * @param EventArgs $args The event arguments.
     */
    public function prePersist(EventArgs $args)
    {
        $obj = $this->dataStorage->getObjectFromArgs($args);
        $mappings = $this->mappingFactory->getMappingsFromObject($obj, $this->dataStorage->getReflectionClass($obj));
        $curFiles = new \SplObjectStorage();

        foreach ($mappings as $mapping) {
            $file = $mapping->getFileUploadPropertyValue();
            if ($file instanceof File) $curFiles[$mapping] = $file;
            $mapping->setFileUploadPropertyValue(null);
        }

        //if ($curFiles) $this->deferredFiles [$mappings[0]->getObj()] = $curFiles;
        if (count($curFiles)) $this->deferredFiles [$obj] = $curFiles;
    }


    /**
     * Store at postFlush event because file namer mey need entity id, at prePersist event
     * system does not now auto generated entity id
     * @param EventArgs $args
     */
    public function postFlush(EventArgs $args)
    {
        if (!$this->deferredFiles) return;

        foreach ($this->deferredFiles as $obj) {
            if (!$this->deferredFiles[$obj]) continue;

            foreach ($this->deferredFiles[$obj] as $mapping) {
                $fileData = $this->fileStorage->upload($mapping, $this->deferredFiles[$obj][$mapping]);
                $mapping->setFileDataPropertyValue($fileData);
            }

            unset($this->deferredFiles[$obj]);
            $this->dataStorage->postFlush($obj, $args);
        }
    }


    /**
     * Update the mapped file for Entity (obj)
     *
     * @param EventArgs  $args
     */
    public function preUpdate(EventArgs $args)
    {
        $mappings = $this->getMappingsFromArgs($args);

        foreach ($mappings as $mapping) {

            //Uploaded or setted file
            $file = $mapping->getFileUploadPropertyValue();

            $currentFileData = $this->dataStorage->currentFieldData($mapping->getFileDataPropertyName(), $args);
            $currentFileName = $currentFileData ? $mapping->resolveFileName($currentFileData['fileName']) : null;


            //If no new file
            if (is_null($file) || !($file instanceof File)) {

                if ($currentFileData) {
                    if (!$this->fileStorage->fileExists($currentFileName)) {

                        $fileNameByWebDir = $_SERVER['DOCUMENT_ROOT'].$currentFileData['path'];

                        if ($this->fileStorage->fileExists($fileNameByWebDir))
                        {
                            $file = new UploadedFile ($fileNameByWebDir,
                                $currentFileData['originalName'], $currentFileData['mimeType'],
                                null,  null, true);
                            $fileData = $this->fileStorage->upload($mapping, $file);
                            $mapping->setFileDataPropertyValue($fileData);
                        }

                    } //Preserve old fileData if current file exist
                    else $mapping->setFileDataPropertyValue($currentFileData);

                }


            } //uploaded file has deleted status
            else if ($file instanceof \Iphp\FileStoreBundle\File\File && $file->isDeleted()) {
                if ($this->fileStorage->removeFile($currentFileName)) $mapping->setFileDataPropertyValue(null);
            } else {

                //Old value (file) exits and uploaded new file
                if ($currentFileData && !$this->fileStorage->isSameFile($file, $currentFileName))
                    //before upload new file delete old file
                    $this->fileStorage->removeFile($currentFileName);

                $fileData = $this->fileStorage->upload($mapping, $file);
                $mapping->setFileDataPropertyValue($fileData);
            }
        }
        $this->dataStorage->recomputeChangeSet($args);
    }


    /**
     * Removes the file if necessary.
     *
     * @param EventArgs $args The event arguments.
     */
    public function postRemove(EventArgs $args)
    {
        $mappings = $this->getMappingsFromArgs($args);

        foreach ($mappings as $mapping) {
            if ($mapping->getDeleteOnRemove()) $this->fileStorage->removeFile($mapping->resolveFileName());
        }

    }

}
