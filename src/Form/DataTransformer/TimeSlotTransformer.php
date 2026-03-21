<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms between DateTimeInterface and time string (H:i format).
 *
 * @implements DataTransformerInterface<\DateTimeInterface|string, string>
 */
class TimeSlotTransformer implements DataTransformerInterface
{
    /**
     * Transforms a DateTimeInterface to a string (H:i).
     *
     * @param \DateTimeInterface|null $dateTime
     */
    public function transform($dateTime): string
    {
        if (null === $dateTime) {
            return '';
        }

        return $dateTime->format('H:i');
    }

    /**
     * Transforms a string (H:i) to a DateTimeImmutable.
     *
     * @param string $timeString
     */
    public function reverseTransform($timeString): ?\DateTimeInterface
    {
        if (!$timeString) {
            return null;
        }

        try {
            // Create DateTimeImmutable from time string (H:i format)
            $dateTime = \DateTimeImmutable::createFromFormat('H:i', $timeString);

            if (false === $dateTime) {
                throw new TransformationFailedException(sprintf('Invalid time format: %s', $timeString));
            }

            return $dateTime;
        } catch (\Exception $e) {
            throw new TransformationFailedException(sprintf('Failed to transform "%s" into a DateTimeInterface.', $timeString), 0, $e);
        }
    }
}
