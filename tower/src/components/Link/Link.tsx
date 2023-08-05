type LinkProps = {
    startMarkerId: string,
    finishMarkerId: string,
}

export default function Link({ startMarkerId, finishMarkerId }: LinkProps)
{
    console.log(`Link from ${startMarkerId} to ${finishMarkerId}`)
    return null;
}
