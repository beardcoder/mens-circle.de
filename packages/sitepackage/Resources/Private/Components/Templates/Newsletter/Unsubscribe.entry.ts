import { animate } from 'motion'

requestAnimationFrame(() => {
    const checkPaths = document.querySelectorAll<SVGPathElement>('.check-path')
    if (checkPaths.length > 0) {
        checkPaths.forEach(checkPath => {
            animate(
                checkPath,
                {
                    pathLength: [0, 1],
                },
                {
                    duration: 0.5,
                    easing: 'ease-out',
                    delay: 0.9,
                },
            )
        })
    }

    const checkCircles = document.querySelectorAll<SVGPathElement>('.check-circle')
    if (checkCircles.length > 0) {
        checkCircles.forEach(checkCircle => {
            animate(
                checkCircle,
                {
                    pathLength: [0, 1.1],
                },
                {
                    duration: 0.6,
                    easing: 'ease-out',
                    delay: 0.3,
                },
            )
        })
    }
})
