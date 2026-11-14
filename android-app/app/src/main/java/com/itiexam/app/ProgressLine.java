package com.itiexam.app;

import android.animation.ValueAnimator;
import android.content.Context;
import android.graphics.Canvas;
import android.graphics.LinearGradient;
import android.graphics.Paint;
import android.graphics.Shader;
import android.util.AttributeSet;
import android.view.View;
import android.view.animation.DecelerateInterpolator;

/** Thin orange-to-blue loading line at the top of the page. */
public class ProgressLine extends View {
    private final Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
    private float shown;          // 0..1 drawn width
    private ValueAnimator anim;

    public ProgressLine(Context c, AttributeSet a) {
        super(c, a);
        setAlpha(0f);
    }

    @Override
    protected void onSizeChanged(int w, int h, int ow, int oh) {
        paint.setShader(new LinearGradient(0, 0, w, 0, new int[]{0xFFF7740F, 0xFFFBB117, 0xFF1F4FD6}, null, Shader.TileMode.CLAMP));
    }

    /** progress 0..100 from the WebView */
    public void setProgress(int progress) {
        float target = Math.max(0.08f, progress / 100f);
        if (progress < 100 && getAlpha() < 1f) animate().alpha(1f).setDuration(120).start();
        if (anim != null) anim.cancel();
        anim = ValueAnimator.ofFloat(shown, target);
        anim.setDuration(220);
        anim.setInterpolator(new DecelerateInterpolator());
        anim.addUpdateListener(new ValueAnimator.AnimatorUpdateListener() {
            @Override public void onAnimationUpdate(ValueAnimator va) {
                shown = (float) va.getAnimatedValue();
                invalidate();
            }
        });
        anim.start();
        if (progress >= 100) {
            animate().alpha(0f).setStartDelay(250).setDuration(250).withEndAction(new Runnable() {
                @Override public void run() { shown = 0; invalidate(); }
            }).start();
        }
    }

    @Override
    protected void onDraw(Canvas c) {
        c.drawRect(0, 0, getWidth() * shown, getHeight(), paint);
    }
}
