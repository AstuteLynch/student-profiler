<?php

/* CVSWHO VISIBILITY HELPERS */


/*
 * PUBLIC:
 * Anyone can view it.
 *
 * SCHOOL ONLY:
 * Only authenticated CVSWHO/CvSU users can view it.
 *
 * PRIVATE:
 * Hidden from other users.
 */
function canViewerSeeVisibility(
    ?string $visibility,
    bool $viewerLoggedIn
): bool {

    $visibility =
        strtolower(
            trim(
                (string) $visibility
            )
        );


    if ($visibility === "public") {
        return true;
    }


    if (
        $visibility === "school_only" &&
        $viewerLoggedIn
    ) {
        return true;
    }


    return false;
}


/*
 * Used when deciding whether the whole profile
 * can be opened.
 */
function canViewerOpenProfile(
    ?string $visibility,
    bool $viewerLoggedIn
): bool {

    return canViewerSeeVisibility(
        $visibility,
        $viewerLoggedIn
    );
}