

<?php
use WPCCrawler\Objects\Crawling\Bot\Objects\CrawlerVariable;

/** @var CrawlerVariable[] $variables */
?>

<div id="wpcc-variables" class="wpcc-generated-container">
    <div class="wpcc-head">
        <div class="wpcc-title"><?php echo e(_wpcc('WP Content Crawler Variables')); ?></div>
        <div class="wpcc-desc"><?php echo e(_wpcc('These variables might be handy when crawling')); ?></div>
    </div>

    <table>
        <thead>
        <tr>
            <th><?php echo e(_wpcc('Name')); ?></th>
            <th><?php echo e(_wpcc('Value')); ?></th>
        </tr>
        </thead>
        <tbody>

        <?php $__currentLoopData = $variables; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variable): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                
                <td><?php echo e($variable->getName()); ?></td>

                
                <td class="<?php echo e($variable->getCssClass()); ?>">
                    <?php echo e($variable->getValue()); ?>

                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        </tbody>
    </table>
</div><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/partials/crawler-variables.blade.php ENDPATH**/ ?>