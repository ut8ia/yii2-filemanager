<?php

namespace ut8ia\filemanager\behaviors;

use yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use ut8ia\filemanager\models\Mediafile;

class ImportImagesBehavior extends Behavior
{
    /**
     * @var
     */
    public $contentField;
    public $altField;
    public $descriptionField;
    public $moduleName;

    /**
     * @inheritdoc
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'importImages'
        ];
    }

    /**
     * @inheritdoc
     */
    public function importImages()
    {
        $content = $this->owner->{$this->contentField};
        // extract attributes from each image and place in $images array
        preg_match_all("#<img(.*?)\/?>#", $content, $matches);
        foreach ($matches[1] as $m) {
            preg_match_all("#(\w+)=['\"]{1}([^'\"]*)#", $m, $attrValues);
            $attributes = [];
            foreach ($attrValues[1] as $key => $attrName) {
                $attributes[$attrName] = $attrValues[2][$key];
            }

            if (!isset($attributes['src'])) {
                continue;
            }

            $mediaFile = new Mediafile();
            $routes = Yii::$app->modules[$this->moduleName]['routes'];
            $thumbs = Yii::$app->modules[$this->moduleName]['thumbs'];
            $alt = (isset($attributes['alt']) && trim($attributes['alt']) != '') ? $attributes['alt'] : $this->owner->{$this->altField};
            $description = $this->owner->{$this->descriptionField};
            if ($mediaFile->importFile($routes, $attributes['src'], $alt, $description, true)) {
                if ($mediaFile->isImage()) {
                    $mediaFile->createThumbs($routes, $thumbs);
                }
                $content = str_replace($attributes['src'], $mediaFile->url, $content);
            }
        }

        $this->owner->{$this->contentField} = $content;
    }


}