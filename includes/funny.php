<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-15
 * Time: 9:55 AM
 */

/**
 * Return a random quote from the movie groundhog day staring bill murray.
 * Also the movie of which branding is based upon.
 *
 * @return mixed
 */
function wpgh_get_random_groundhogday_quote()
{
    $quotes = array();

    $quotes[] = "I'm not going to live by their rules anymore.";
    $quotes[] = "When Chekhov saw the long winter, he saw a winter bleak and dark and bereft of hope. Yet we know that winter is just another step in the cycle of life. But standing here among the people of Punxsutawney and basking in the warmth of their hearths and hearts, I couldn't imagine a better fate than a long and lustrous winter.";
    $quotes[] = "Hi, three cheeseburgers, two large fries, two milkshakes, and one large coke.";;
    $quotes[] = "It's the same thing every day, Clean up your room, stand up straight, pick up your feet, take it like a man, be nice to your sister, don't mix beer and wine ever, Oh yeah, don't drive on the railroad tracks.";
    $quotes[] = "I'm a god, I'm not the God. I don't think.";
    $quotes[] = "Don't drive angry! Don't drive angry!";
    $quotes[] = "I'm betting he's going to swerve first.";
    $quotes[] = "You want a prediction about the weather? You're asking the wrong Phil. I'm going to give you a prediction about this winter? It's going to be cold, it's going to be dark and it's going to last you for the rest of your lives!";
    $quotes[] = "We mustn't keep our audience waiting.";
    $quotes[] = "Okay campers, rise and shine, and don't forget your booties cause its cold out there...its cold out there every day.";
    $quotes[] = "I peg you as a glass half empty kinda guy.";
    $quotes[] = "Why would anybody steal a groundhog? <i>I can probably think of a couple of reasons... pervert.</i>";
    $quotes[] = "Well, what if there is no tomorrow? There wasn't one today.";
    $quotes[] = "Did he actually refer to himself as \"the talent\"?";
    $quotes[] = "Did you sleep well Mr. Connors?";

    $quotes = apply_filters( 'add_movie_quotes', $quotes );

    $quote = rand( 0, count( $quotes ) - 1 );

    return $quotes[ $quote ];
}